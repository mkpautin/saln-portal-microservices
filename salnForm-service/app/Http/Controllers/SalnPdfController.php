<?php

namespace App\Http\Controllers;

use App\Services\SalnPdf\SalnPdfGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SalnPdfController extends Controller
{
    public function __construct(
        private readonly SalnPdfGeneratorService $generator,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function generate(Request $request): JsonResponse
    {
        $this->persistUpdateFromRequest($request);

        $userId = $this->userIdFromAuth();
        $salnForm = $this->findSalnForm($userId);

        if (! $salnForm) {
            $salnForm = $this->blankSalnForm($userId);
        }

        $this->assertPdfReadiness($salnForm->toArray());

        try {
            $generated = $this->generator->generate($salnForm);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'PDF generator failed. Please try again.',
            ], 500);
        }

        $expiresAt = now()->addMinutes($this->pdfUrlTtlMinutes());

        try {
            $downloadUrl = $this->storePdfAndCreateUrl($generated['path'], $generated['filename'], $userId, $expiresAt);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'PDF upload failed. Please try again.',
            ], 500);
        } finally {
            if (is_file($generated['path'])) {
                unlink($generated['path']);
            }
        }

        return response()->json([
            'message' => 'PDF ready.',
            'download_url' => $downloadUrl,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function download(Request $request, string $token): JsonResponse
    {
        return response()->json([
            'message' => 'PDF downloads are served through the pre-signed URL returned by /api/saln/pdf.',
        ], 410);
    }

    /**
     * @throws ValidationException
     */
    private function assertPdfReadiness(array $salnForm): void
    {
        return;
    }

    private function persistUpdateFromRequest(Request $request): void
    {
        if (! $this->containsSalnPayload($request)) {
            return;
        }

        app(SalnFormController::class)->update($request);
    }

    private function userIdFromAuth(): int
    {
        $payload = auth()->payload();

        return (int) $payload->get('sub');
    }

    private function findSalnForm(int $userId): ?\App\Models\SalnForm
    {
        return \App\Models\SalnForm::query()->where('user_id', $userId)->first();
    }

    private function pdfUrlTtlMinutes(): int
    {
        return max(1, (int) config('services.saln_pdf.url_ttl_minutes', 10));
    }

    private function storePdfAndCreateUrl(string $path, string $filename, int $userId, \DateTimeInterface $expiresAt): string
    {
        $key = $this->pdfObjectKey($userId, $filename);
        $stream = fopen($path, 'r');

        if ($stream === false) {
            throw new RuntimeException('Generated PDF unavailable.');
        }

        $disk = Storage::disk((string) config('services.saln_pdf.disk', 's3'));

        try {
            if ($disk instanceof AwsS3V3Adapter) {
                $this->putS3ObjectWithoutAcl($disk, $key, $stream);
            } else {
                $disk->put($key, $stream, [
                    'ContentType' => 'application/pdf',
                ]);
            }
        } finally {
            fclose($stream);
        }

        return $disk->temporaryUrl($key, $expiresAt, [
            'ResponseContentType' => 'application/pdf',
            'ResponseContentDisposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function pdfObjectKey(int $userId, string $filename): string
    {
        $prefix = trim((string) config('services.saln_pdf.prefix', 'saln-pdf'), '/');

        return ($prefix !== '' ? $prefix.'/' : '').$userId.'/'.$filename;
    }

    /**
     * Flysystem's S3 adapter sends an ACL on writes. Bucket-owner-enforced S3
     * buckets reject ACLs, so PDF uploads use the AWS client directly.
     *
     * @param  resource  $body
     */
    private function putS3ObjectWithoutAcl(AwsS3V3Adapter $disk, string $key, mixed $body): void
    {
        $config = $disk->getConfig();
        $root = trim((string) ($config['root'] ?? ''), '/');
        $objectKey = $root !== '' ? $root.'/'.ltrim($key, '/') : ltrim($key, '/');

        $disk->getClient()->putObject([
            'Bucket' => $config['bucket'],
            'Key' => $objectKey,
            'Body' => $body,
            'ContentType' => 'application/pdf',
        ]);
    }

    private function blankSalnForm(int $userId): \App\Models\SalnForm
    {
        return new \App\Models\SalnForm([
            'user_id' => $userId,
            'compliance_type' => 'assumption',
            'compliance_date' => null,
            'compliance_year' => null,
            'declarant' => [],
            'spouse' => [],
            'filing_type' => 'joint',
            'additional_spouses' => [],
            'children' => [],
            'real_properties' => [],
            'personal_properties' => [],
            'business_interests' => [],
            'relatives_in_government_service' => [],
            'liabilities' => [],
            'total_assets' => 0,
            'total_liabilities' => 0,
            'net_worth' => 0,
        ]);
    }

    private function containsSalnPayload(Request $request): bool
    {
        return $request->hasAny([
            'compliance_type',
            'assumption_date',
            'annual_year',
            'exit_date',
            'declarant',
            'spouse',
            'filing_type',
            'additional_spouses',
            'children',
            'real_properties',
            'personal_properties',
            'business_interests',
            'relatives_in_government_service',
            'liabilities',
        ]);
    }
}
