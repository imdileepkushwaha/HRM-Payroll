<?php

require_once __DIR__ . '/../lib/fpdf.php';
require_once __DIR__ . '/portal_docs_data.php';

class PortalDocumentationPdf extends FPDF
{
    private string $docTitle = '';
    private array $accent = [109, 40, 217];
    private array $sectionPages = [];
    private int $sectionNo = 0;

    public function setDocumentMeta(string $title, array $accent): void
    {
        $this->docTitle = $title;
        $this->accent = $accent;
    }

    public function Footer(): void
    {
        $this->SetY(-14);
        $this->SetDrawColor(226, 232, 240);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->SetY(-12);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(95, 8, $this->docTitle . ' — User Guide', 0, 0, 'L');
        $this->Cell(95, 8, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'R');
    }

    public function renderFullGuide(array $doc, bool $fillToc = true): array
    {
        $meta = portal_docs_meta();
        $this->AliasNbPages();
        $this->SetAutoPageBreak(true, 18);
        $this->setDocumentMeta($doc['title'], $doc['accent']);
        $this->sectionPages = [];

        $this->renderCover($doc, $meta);
        $this->renderIntroPage($doc, $meta);
        $this->renderWorkflowsPage($doc);
        $this->renderAllSections($doc);

        if ($fillToc) {
            $this->renderTocPage($doc);
        }

        $this->renderClosingPage($meta);

        return $this->sectionPages;
    }

    public function getSectionPages(): array
    {
        return $this->sectionPages;
    }

    private function renderCover(array $doc, array $meta): void
    {
        $this->AddPage();
        [$r, $g, $b] = $doc['accent'];

        $this->SetFillColor($r, $g, $b);
        $this->Rect(0, 0, 210, 95, 'F');

        $this->SetFillColor(min(255, $r + 30), min(255, $g + 20), min(255, $b + 20));
        $this->Rect(140, 55, 90, 90, 'F');

        $this->SetFillColor(255, 255, 255);
        $this->Rect(18, 28, 28, 28, 'F');
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor($r, $g, $b);
        $this->SetXY(18, 36);
        $this->Cell(28, 10, strtoupper(substr($doc['title'], 0, 1)), 0, 1, 'C');

        $this->SetXY(18, 108);
        $this->SetFont('Arial', 'B', 28);
        $this->SetTextColor(15, 23, 42);
        $this->MultiCell(174, 12, $doc['title'] . "\nUser Guide", 0, 'L');

        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(71, 85, 105);
        $this->MultiCell(174, 7, $doc['subtitle']);

        $this->Ln(6);
        $this->SetFillColor($r, $g, $b);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(52, 8, strtoupper($meta['product']), 0, 1, 'C', true);

        $this->SetTextColor(100, 116, 139);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, $meta['generated_label'] . '  |  ' . date('F Y'), 0, 1);

        $this->SetY(250);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(0, 5, 'Confidential — for authorised users only', 0, 1, 'C');
    }

    private function renderIntroPage(array $doc, array $meta): void
    {
        $this->AddPage();
        $this->sectionHeader('Introduction', 0);
        $this->bodyText($doc['intro']);
        $this->Ln(4);

        $this->featureBox('Document scope', [
            'Portal: ' . $doc['title'],
            'Product: ' . $meta['product'],
            'Modules documented: ' . $this->countModules($doc),
            'Audience: ' . ($doc['title'] === 'Admin Portal' ? 'HR admins & payroll staff' : 'All employees'),
        ]);
    }

    private function renderWorkflowsPage(array $doc): void
    {
        if (empty($doc['workflows'])) {
            return;
        }

        $this->AddPage();
        $this->sectionHeader('Key workflows', 0);
        $this->bodyText('These diagrams show how common tasks flow through the system.');
        $this->Ln(3);

        foreach ($doc['workflows'] as $wf) {
            $this->workflowDiagram($wf['title'], $wf['steps']);
            $this->Ln(4);
        }
    }

    private function renderTocPage(array $doc): void
    {
        $this->AddPage();
        $this->sectionHeader('Table of contents', 0);
        $this->Ln(2);

        $this->SetFont('Arial', '', 10);
        $n = 0;
        foreach ($doc['sections'] as $section) {
            $n++;
            $pageNo = $this->sectionPages[$n] ?? '-';
            $this->SetTextColor(51, 65, 85);
            $this->Cell(155, 7, $n . '. ' . $section['title'], 0, 0);
            $this->SetTextColor(124, 58, 237);
            $this->Cell(0, 7, (string) $pageNo, 0, 1, 'R');
            foreach ($section['modules'] as $module) {
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Arial', '', 9);
                $this->Cell(12);
                $this->Cell(143, 6, $module['name'], 0, 1);
                $this->SetFont('Arial', '', 10);
            }
            $this->Ln(1);
        }
    }

    private function renderAllSections(array $doc): void
    {
        $this->sectionNo = 0;

        foreach ($doc['sections'] as $section) {
            $this->sectionNo++;
            $this->AddPage();
            $this->sectionPages[$this->sectionNo] = $this->PageNo();

            $this->chapterBanner($section['title'], $this->sectionNo);

            foreach ($section['modules'] as $module) {
                if ($this->GetY() > 240) {
                    $this->AddPage();
                }
                $this->moduleBlock($module);
                $this->Ln(3);
            }
        }
    }

    private function renderClosingPage(array $meta): void
    {
        $this->AddPage();
        [$r, $g, $b] = $this->accent;
        $this->SetFillColor($r, $g, $b);
        $this->Rect(15, 40, 180, 40, 'F');
        $this->SetXY(15, 52);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(180, 10, 'Thank you for using ' . $meta['product'], 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(180, 7, 'For setup and technical configuration, refer to your system administrator.', 0, 1, 'C');

        $this->SetY(100);
        $this->SetTextColor(51, 65, 85);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Support checklist', 0, 1);
        $this->SetFont('Arial', '', 10);
        $tips = [
            'Ensure attendance is synced before monthly payroll.',
            'Keep employee documents verified for compliance.',
            'Review pending approvals daily to avoid payroll delays.',
            'Configure SMTP in Settings for slip email delivery.',
            'Employees should use the latest portal password policy.',
        ];
        foreach ($tips as $tip) {
            $this->bulletLine($tip);
        }
    }

    private function sectionHeader(string $title, int $num): void
    {
        [$r, $g, $b] = $this->accent;
        $this->SetFillColor($r, $g, $b);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $label = $num > 0 ? $num . '. ' . $title : $title;
        $this->Cell(0, 10, $label, 0, 1, 'L', true);
        $this->Ln(4);
    }

    private function chapterBanner(string $title, int $num): void
    {
        [$r, $g, $b] = $this->accent;
        $this->SetFillColor(248, 250, 252);
        $this->SetDrawColor($r, $g, $b);
        $this->SetLineWidth(0.8);
        $this->Rect(15, $this->GetY(), 180, 22, 'DF');
        $this->SetLineWidth(0.2);

        $this->SetXY(20, $this->GetY() + 5);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor($r, $g, $b);
        $this->Cell(0, 5, 'CHAPTER ' . $num, 0, 1);
        $this->SetX(20);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 8, $title, 0, 1);
        $this->Ln(8);
    }

    private function moduleBlock(array $module): void
    {
        [$r, $g, $b] = $this->accent;
        $y = $this->GetY();
        $this->SetFillColor($r, $g, $b);
        $this->Rect(15, $y, 3, 20, 'F');

        $this->SetXY(20, $y);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 7, $module['name'], 0, 1);

        $this->SetX(20);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(71, 85, 105);
        $this->MultiCell(175, 5, $module['summary']);
        $this->Ln(2);

        if (!empty($module['features'])) {
            $this->SetX(20);
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor($r, $g, $b);
            $this->Cell(0, 6, 'KEY FEATURES', 0, 1);
            foreach ($module['features'] as $feature) {
                $this->bulletLine($feature, 22);
            }
            $this->Ln(1);
        }

        if (!empty($module['steps'])) {
            $this->SetX(20);
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor(14, 116, 144);
            $this->Cell(0, 6, 'HOW TO USE', 0, 1);
            $n = 1;
            foreach ($module['steps'] as $step) {
                $this->SetX(22);
                $this->SetFont('Arial', '', 9);
                $this->SetTextColor(51, 65, 85);
                $this->MultiCell(173, 5, $n . '. ' . $step);
                $n++;
            }
        }

        $this->SetDrawColor(226, 232, 240);
        $this->Line(15, $this->GetY() + 2, 195, $this->GetY() + 2);
        $this->Ln(4);
    }

    private function featureBox(string $title, array $lines): void
    {
        [$r, $g, $b] = $this->accent;
        $this->SetFillColor(245, 243, 255);
        $this->SetDrawColor(221, 214, 254);
        $y = $this->GetY();
        $this->Rect(15, $y, 180, 8 + count($lines) * 6, 'DF');
        $this->SetXY(18, $y + 3);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor($r, $g, $b);
        $this->Cell(0, 6, $title, 0, 1);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(51, 65, 85);
        foreach ($lines as $line) {
            $this->SetX(20);
            $this->Cell(0, 5, chr(149) . ' ' . $line, 0, 1);
        }
        $this->SetY($y + 12 + count($lines) * 6);
    }

    private function workflowDiagram(string $title, array $steps): void
    {
        [$r, $g, $b] = $this->accent;
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 7, $title, 0, 1);

        $boxW = 32;
        $gap = 4;
        $startX = 15;
        $y = $this->GetY();

        $count = count($steps);
        $totalW = $count * $boxW + ($count - 1) * $gap;
        if ($totalW > 180) {
            $boxW = (180 - ($count - 1) * $gap) / $count;
            $totalW = $count * $boxW + ($count - 1) * $gap;
        }
        $startX = 15 + (180 - $totalW) / 2;

        for ($i = 0; $i < $count; $i++) {
            $x = $startX + $i * ($boxW + $gap);
            $this->SetFillColor($r, $g, $b);
            $this->Rect($x, $y, $boxW, 14, 'F');
            $this->SetXY($x + 1, $y + 2);
            $this->SetFont('Arial', '', 6.5);
            $this->SetTextColor(255, 255, 255);
            $this->MultiCell($boxW - 2, 3.5, $steps[$i], 0, 'C');

            if ($i < $count - 1) {
                $ax = $x + $boxW;
                $this->SetDrawColor($r, $g, $b);
                $this->Line($ax, $y + 7, $ax + $gap, $y + 7);
                $this->Line($ax + $gap - 1, $y + 6, $ax + $gap, $y + 7);
                $this->Line($ax + $gap - 1, $y + 8, $ax + $gap, $y + 7);
            }
        }
        $this->SetY($y + 18);
    }

    private function bodyText(string $text): void
    {
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(51, 65, 85);
        $this->MultiCell(180, 5.5, $text);
    }

    private function bulletLine(string $text, float $x = 20): void
    {
        $this->SetX($x);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(51, 65, 85);
        $this->MultiCell(175 - ($x - 15), 5, chr(149) . ' ' . $text);
    }

    private function countModules(array $doc): int
    {
        $n = 0;
        foreach ($doc['sections'] as $section) {
            $n += count($section['modules']);
        }
        return $n;
    }
}

function generate_portal_documentation_pdf(array $doc): string
{
    $pdf = new PortalDocumentationPdf('P', 'mm', 'A4');
    $pdf->renderFullGuide($doc, true);
    return $pdf->Output('S');
}

function write_portal_documentation_pdfs(string $outputDir): array
{
    if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
        throw new RuntimeException('Cannot create output directory: ' . $outputDir);
    }

    $files = [];
    $map = [
        'Admin-Portal-User-Guide.pdf' => get_admin_portal_documentation(),
        'Employee-Portal-User-Guide.pdf' => get_employee_portal_documentation(),
    ];

    foreach ($map as $filename => $doc) {
        $path = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($path, generate_portal_documentation_pdf($doc));
        $files[] = $path;
    }

    return $files;
}
