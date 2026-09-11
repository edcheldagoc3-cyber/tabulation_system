<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ReportModel;
use App\Models\CategoryModel;
use App\Models\EventModel;

class ReportController extends Controller {

    // Reports dashboard
    public function index() {
        Auth::requireAnyRole(['admin', 'tabulator']);
        
        $events = ReportModel::getEventsWithResults();
        $categories = ReportModel::getReleasedCategories();
        
        $this->view('reports/index', [
            'events' => $events,
            'categories' => $categories,
        ]);
    }

    // Category results report
    public function category($categoryId) {
        Auth::requireAnyRole(['admin', 'tabulator']);
        
        $results = ReportModel::getCategoryReport($categoryId);
        $category = CategoryModel::getById($categoryId);
        
        if (empty($results)) {
            die('No released results found for this category.');
        }
        
        $this->view('reports/category', [
            'results' => $results,
            'category' => $category,
        ]);
    }

    // Event summary report
    public function event($eventId) {
        Auth::requireAnyRole(['admin', 'tabulator']);
        
        $summary = ReportModel::getEventReport($eventId);
        $event = EventModel::getById($eventId);
        
        if (empty($summary)) {
            die('No released categories found for this event.');
        }
        
        $this->view('reports/event', [
            'summary' => $summary,
            'event' => $event,
        ]);
    }

    // Individual score sheet
    public function scoreSheet($contestantId, $categoryId) {
        Auth::requireAnyRole(['admin', 'tabulator']);
        
        $scores = ReportModel::getContestantScoreSheet($contestantId, $categoryId);
        
        if (empty($scores)) {
            die('No scores found for this contestant.');
        }
        
        $this->view('reports/score_sheets', [
            'scores' => $scores,
        ]);
    }

    // Printable version (no sidebar)
    public function printable($categoryId) {
        // No auth required – but only released results are shown
        $results = ReportModel::getCategoryReport($categoryId);
        $category = CategoryModel::getById($categoryId);
        
        if (empty($results)) {
            die('No released results found for this category.');
        }
        
        // Use a minimal layout without sidebar
        $this->view('reports/printable', [
            'results' => $results,
            'category' => $category,
        ]);
    }

    // CSV Export
    public function exportCsv($categoryId) {
        Auth::requireAnyRole(['admin', 'tabulator']);
        
        $results = ReportModel::getCategoryReport($categoryId);
        $category = CategoryModel::getById($categoryId);
        
        if (empty($results)) {
            die('No data to export.');
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $category->name . '_results.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, ['Event', 'Category', 'Event Date', 'Rank', 'Contestant', 'Type', 'Final Score', 'Judge Scores', 'Released By', 'Released At']);
        
        // Write data
        foreach ($results as $row) {
            fputcsv($output, [
                $row->event_name,
                $row->category_name,
                $row->event_date,
                $row->final_rank,
                $row->contestant_name,
                $row->contestant_type,
                $row->final_score,
                $row->judge_scores,
                $row->released_by_name ?? '',
                $row->released_at ?? '',
            ]);
        }
        
        fclose($output);
        exit;
    }

    // Official ranked results PDF.
    public function exportPdf($categoryId) {
        Auth::requireAnyRole(['admin', 'tabulator']);
        $results = ReportModel::getCategoryReport($categoryId);
        $category = CategoryModel::getById($categoryId);
        if (empty($results) || !$category) {
            die('No released results found for this category.');
        }

        $first = $results[0];
        $meta = [
            'Event: ' . ($first->event_name ?? ''),
            'Category: ' . ($category->name ?? ''),
            'Event date: ' . ($first->event_date ?? ''),
            'Released by: ' . ($first->released_by_name ?? 'Not recorded'),
            'Released at: ' . ($first->released_at ?? 'Not recorded'),
        ];
        $rows = [];
        foreach ($results as $result) {
            $rows[] = [
                '#' . (int)$result->final_rank,
                $result->contestant_name,
                ucfirst($result->contestant_type ?? 'solo'),
                number_format((float)$result->final_score, 2),
            ];
        }
        $this->downloadPdf(($category->name ?? 'category') . '_official_results.pdf', 'Official Results', $meta, ['Rank', 'Contestant', 'Type', 'Final score'], $rows);
    }

    // Official detailed score breakdown PDF.
    public function exportBreakdownPdf($categoryId) {
        Auth::requireAnyRole(['admin', 'tabulator']);
        $rowsData = ReportModel::getCategoryScoreBreakdown($categoryId);
        $category = CategoryModel::getById($categoryId);
        if (empty($rowsData) || !$category) {
            die('No released score breakdown found for this category.');
        }

        $first = $rowsData[0];
        $meta = [
            'Event: ' . ($first->event_name ?? ''),
            'Category: ' . ($category->name ?? ''),
            'Event date: ' . ($first->event_date ?? ''),
            'Released by: ' . ($first->released_by_name ?? 'Not recorded'),
            'Released at: ' . ($first->released_at ?? 'Not recorded'),
        ];
        $rows = [];
        foreach ($rowsData as $row) {
            $rows[] = [
                '#' . (int)$row->final_rank,
                $row->contestant_name,
                $row->judge_name,
                $row->criterion_name,
                number_format((float)$row->score_value, 2),
                number_format((float)$row->weight_percent, 2) . '%',
                $row->submitted_at ?? '',
            ];
        }
        $this->downloadPdf(($category->name ?? 'category') . '_score_breakdown.pdf', 'Detailed Score Breakdown', $meta, ['Rank', 'Contestant', 'Judge', 'Criterion', 'Score', 'Weight', 'Submitted'], $rows, true);
    }

    private function downloadPdf($filename, $title, array $meta, array $headers, array $rows, bool $landscape = false) {
        $width = $landscape ? 842 : 612;
        $height = $landscape ? 612 : 792;
        $margin = 36;
        $tableWidth = $width - ($margin * 2);
        $columnWidths = $this->pdfColumnWidths(count($headers), $tableWidth, $landscape);
        $pages = [];
        $page = '';
        $y = $height - $margin;
        $pageNumber = 1;

        $startPage = function () use (&$page, &$y, $width, $height, $margin, $title, $meta, &$pageNumber) {
            $page = '';
            $page .= $this->pdfRect(0, $height - 92, $width, 92, [0.427, 0.102, 0.169]);
            $page .= $this->pdfTextAt($margin, $height - 42, $title, 22, [1, 1, 1]);
            $page .= $this->pdfTextAt($margin, $height - 64, 'USTP TabulaSys | Official Competition Report', 9, [0.94, 0.88, 0.86]);
            $y = $height - 118;
            $metaX = $margin;
            foreach ($meta as $index => $item) {
                $page .= $this->pdfTextAt($metaX, $y, $item, 9, [0.25, 0.25, 0.25]);
                $metaX += $width > 700 ? 245 : 180;
                if (($index + 1) % ($width > 700 ? 3 : 2) === 0) {
                    $metaX = $margin;
                    $y -= 15;
                }
            }
            $y -= 12;
        };
        $drawTableHeader = function () use (&$page, &$y, $headers, $columnWidths, $margin) {
            $headerHeight = 24;
            $page .= $this->pdfRect($margin, $y - $headerHeight + 5, array_sum($columnWidths), $headerHeight, [0.427, 0.102, 0.169]);
            $x = $margin;
            foreach ($headers as $index => $header) {
                $page .= $this->pdfTextAt($x + 6, $y - 11, $header, 8, [1, 1, 1]);
                $x += $columnWidths[$index];
            }
            $y -= $headerHeight;
        };

        $startPage();
        $drawTableHeader();
        foreach ($rows as $rowIndex => $row) {
            $wrapped = [];
            $rowHeight = 18;
            foreach ($row as $index => $value) {
                $wrapped[$index] = $this->pdfWrap($value, max(8, (int)floor($columnWidths[$index] / 5.2)));
                $rowHeight = max($rowHeight, count($wrapped[$index]) * 11 + 9);
            }
            if ($y - $rowHeight < 46) {
                $page .= $this->pdfTextAt($width - 100, 22, 'Page ' . $pageNumber, 8, [0.45, 0.45, 0.45]);
                $pages[] = $page;
                $pageNumber++;
                $startPage();
                $drawTableHeader();
            }
            if ($rowIndex % 2 === 1) $page .= $this->pdfRect($margin, $y - $rowHeight + 5, array_sum($columnWidths), $rowHeight, [0.97, 0.95, 0.94]);
            $x = $margin;
            foreach ($wrapped as $index => $cellLines) {
                foreach ($cellLines as $lineIndex => $line) {
                    $page .= $this->pdfTextAt($x + 6, $y - 10 - ($lineIndex * 11), $line, 8, [0.16, 0.16, 0.16]);
                }
                $x += $columnWidths[$index];
            }
            $y -= $rowHeight;
            $page .= $this->pdfLine($margin, $y + 5, $margin + $tableWidth, $y + 5, [0.86, 0.83, 0.81]);
        }
        $page .= $this->pdfTextAt($margin, 22, 'Generated ' . date('Y-m-d H:i:s'), 8, [0.45, 0.45, 0.45]);
        $page .= $this->pdfTextAt($width - 100, 22, 'Page ' . $pageNumber, 8, [0.45, 0.45, 0.45]);
        $pages[] = $page;

        $objects = ['<< /Type /Catalog /Pages 2 0 R >>'];
        $pageObjectIds = [];
        $nextId = 4;
        foreach ($pages as $unused) {
            $pageObjectIds[] = $nextId;
            $nextId += 2;
        }
        $kids = implode(' ', array_map(static fn($id) => $id . ' 0 R', $pageObjectIds));
        $objects[] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pages) . ' >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        foreach ($pages as $index => $content) {
            $contentId = $pageObjectIds[$index] + 1;
            $objects[] = '<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 3 0 R >> >> /MediaBox [0 0 ' . $width . ' ' . $height . '] /Contents ' . $contentId . ' 0 R >>';
            $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $objectNumber = $id + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $objectNumber . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_.-]+/', '_', $filename) . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function pdfColumnWidths($count, $tableWidth, $landscape) {
        if ($landscape && $count === 7) return [42, 150, 125, 145, 55, 55, 130];
        if ($count === 4) return [55, $tableWidth - 245, 90, 100];
        return array_fill(0, $count, $tableWidth / max(1, $count));
    }

    private function pdfWrap($value, $maxChars) {
        $text = $this->pdfText($value);
        return $text === '' ? [''] : (array)preg_split('/\s*\|\s*|\s+/', wordwrap($text, $maxChars, "\n", true));
    }

    private function pdfRect($x, $y, $width, $height, array $color) {
        return sprintf("q %.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f Q\n", $color[0], $color[1], $color[2], $x, $y, $width, $height);
    }

    private function pdfLine($x1, $y1, $x2, $y2, array $color) {
        return sprintf("q %.3f %.3f %.3f RG 0.5 w %.2f %.2f m %.2f %.2f l S Q\n", $color[0], $color[1], $color[2], $x1, $y1, $x2, $y2);
    }

    private function pdfTextAt($x, $y, $value, $size, array $color) {
        return sprintf("BT /F1 %d Tf %.3f %.3f %.3f rg %.2f %.2f Td (%s) Tj ET\n", $size, $color[0], $color[1], $color[2], $x, $y, $this->pdfText($value));
    }

    private function pdfText($value) {
        $value = (string)$value;
        $value = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $value ?: '');
    }
}
