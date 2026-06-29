<?php

require_once __DIR__ . '/../lib/fpdf.php';
require_once __DIR__ . '/settings_helper.php';

function generate_fnf_settlement_pdf(array $exit, array $employee, array $fnf, array $settings): string
{
    $pdf = new FPDF();
    $pdf->AddPage();
    $company = trim($settings['company_name'] ?? '') ?: 'Company';

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, $company, 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 8, 'Full & Final Settlement Statement', 0, 1, 'C');
    $pdf->Ln(4);

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 6, 'Employee:', 0, 0);
    $pdf->Cell(0, 6, ($employee['name'] ?? '') . ' (' . ($employee['emp_id'] ?? '') . ')', 0, 1);
    $pdf->Cell(50, 6, 'Department:', 0, 0);
    $pdf->Cell(0, 6, $employee['department'] ?? '—', 0, 1);
    $pdf->Cell(50, 6, 'Last working day:', 0, 0);
    $pdf->Cell(0, 6, $exit['last_working_day'] ?? '—', 0, 1);
    $pdf->Cell(50, 6, 'Exit type:', 0, 0);
    $pdf->Cell(0, 6, ucfirst($exit['exit_type'] ?? 'resignation'), 0, 1);
    $pdf->Cell(50, 6, 'Settlement status:', 0, 0);
    $pdf->Cell(0, 6, ucfirst($fnf['status'] ?? 'draft'), 0, 1);
    $pdf->Ln(6);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, 'Settlement breakdown', 0, 1);
    $pdf->SetFont('Arial', '', 10);

    $rows = [
        ['Salary due', (float) ($fnf['salary_due'] ?? 0)],
        ['Leave encashment', (float) ($fnf['leave_encashment'] ?? 0)],
        ['Notice pay', (float) ($fnf['notice_pay'] ?? 0)],
        ['Deductions', -(float) ($fnf['deductions'] ?? 0)],
    ];
    foreach ($rows as [$label, $amt]) {
        $pdf->Cell(100, 7, $label, 1, 0);
        $pdf->Cell(0, 7, number_format(abs($amt), 2), 1, 1, 'R');
    }
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(100, 8, 'Net payable', 1, 0);
    $pdf->Cell(0, 8, number_format((float) ($fnf['net_payable'] ?? 0), 2), 1, 1, 'R');

    if (!empty($fnf['notes'])) {
        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->MultiCell(0, 5, 'Notes: ' . $fnf['notes']);
    }

    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(0, 4, 'This is a system-generated draft. Verify all amounts before final payment. Generated on ' . date('d M Y H:i') . '.');

    return $pdf->Output('S');
}
