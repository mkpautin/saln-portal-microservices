<?php

namespace App\Http\Controllers;

use App\Services\SalnPdf\SalnPdfGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    public function generate(Request $request): BinaryFileResponse|JsonResponse
    {
        $this->persistDraftFromRequest($request);

        $user = Auth::user();
        $user->unsetRelation('salnForm');
        $salnForm = $user->salnForm;

        if (! $salnForm) {
            throw ValidationException::withMessages([
                'pdf' => 'No SALN form found. Please save your form before generating a PDF.',
            ]);
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

        return response()->download($generated['path'], $generated['filename'], [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @throws ValidationException
     */
    private function assertPdfReadiness(array $salnForm): void
    {
        $errors = [];
        $complianceType = $salnForm['compliance_type'] ?? null;

        if (! in_array($complianceType, ['assumption', 'annual', 'exit'], true)) {
            $errors['compliance_type'] = 'Compliance type is required.';
        }

        if ($complianceType === 'assumption' && empty($salnForm['compliance_date'])) {
            $errors['assumption_date'] = 'Assumption date is required.';
        }

        if ($complianceType === 'annual' && empty($salnForm['compliance_year'])) {
            $errors['annual_year'] = 'Annual year is required.';
        }

        if ($complianceType === 'exit' && empty($salnForm['compliance_date'])) {
            $errors['exit_date'] = 'Exit date is required.';
        }

        $declarant = is_array($salnForm['declarant'] ?? null) ? $salnForm['declarant'] : [];
        foreach (['family_name', 'first_name', 'position', 'agency_office', 'office_address'] as $field) {
            if (trim((string) ($declarant[$field] ?? '')) === '') {
                $errors['declarant.'.$field] = 'Declarant '.$field.' is required.';
            }
        }

        if (! in_array($salnForm['filing_type'] ?? null, ['joint', 'separate', 'not_applicable'], true)) {
            $errors['filing_type'] = 'Filing type is required.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function persistDraftFromRequest(Request $request): void
    {
        if (! $this->containsSalnPayload($request)) {
            return;
        }

        app(SalnFormController::class)->draft($request);
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
