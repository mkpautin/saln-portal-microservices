<?php

namespace App\Http\Controllers;

use App\Services\SalnPdf\SalnPdfGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        $this->persistDraftFromRequest($request);

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
                'message' => 'PDF generation failed. Please try again.',
            ], 500);
        }

        $token = Str::random(64);
        $expiresAt = now()->addMinutes($this->pdfTtlMinutes());

        Cache::put($this->cacheKey($token), [
            'path' => $generated['path'],
            'filename' => $generated['filename'],
            'user_id' => $userId,
        ], $expiresAt);

        return response()->json([
            'message' => 'PDF ready.',
            'download_url' => url('/api/saln/pdf/download/'.$token),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function download(Request $request, string $token): BinaryFileResponse|JsonResponse
    {
        $userId = $this->userIdFromAuth();
        $payload = Cache::pull($this->cacheKey($token));

        if (! is_array($payload) || (int) ($payload['user_id'] ?? 0) !== $userId) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        $path = $payload['path'] ?? null;
        $filename = $payload['filename'] ?? 'saln.pdf';

        if (! is_string($path) || ! is_file($path)) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @throws ValidationException
     */
    private function assertPdfReadiness(array $salnForm): void
    {
        return;
    }

    private function persistDraftFromRequest(Request $request): void
    {
        if (! $this->containsSalnPayload($request)) {
            return;
        }

        app(SalnFormController::class)->draft($request);
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

    private function pdfTtlMinutes(): int
    {
        return (int) (env('SALN_PDF_TTL_MINUTES', 30));
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

    private function cacheKey(string $token): string
    {
        return 'saln_pdf_token:'.$token;
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
