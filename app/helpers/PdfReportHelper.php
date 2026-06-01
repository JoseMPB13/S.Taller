<?php

namespace App\Helpers;

// Cargar la librería FPDF nativamente
require_once __DIR__ . '/fpdf/fpdf.php';

/**
 * Helper para la generación de reportes PDF estilizados en S.Taller.
 */
class PdfReportHelper extends \FPDF {

    /**
     * Título principal del documento
     * @var string
     */
    public $documentTitle = 'ORDEN DE TRABAJO';

    /**
     * Subtítulo o código identificador del documento
     * @var string
     */
    public $documentSubTitle = '';

    /**
     * Decodifica una cadena de UTF-8 a ISO-8859-1 para compatibilidad con FPDF.
     * Evita el uso de la función obsoleta utf8_decode() en PHP 8.2+.
     * 
     * @param string $str
     * @return string
     */
    public function decode($str) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($str ?? '', 'ISO-8859-1', 'UTF-8');
        } elseif (function_exists('iconv')) {
            return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str ?? '');
        }
        return @utf8_decode($str ?? '');
    }

    /**
     * Sobrescribe el método Header de FPDF para estandarizar el encabezado.
     */
    public function Header() {
        // Nombre de la empresa
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(30, 41, 59); // Color primario oscuro (#1e293b)
        $this->Cell(120, 8, 'S.TALLER', 0, 0, 'L');
        
        // Tipo de documento
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(16, 185, 129); // Color acento verde (#10b981)
        $this->Cell(0, 8, strtoupper($this->decode($this->documentTitle)), 0, 1, 'R');
        
        // Datos de contacto y ubicación
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 116, 139); // Gris suave (#64748b)
        $this->Cell(120, 5, $this->decode('Servicios Mecánicos Integrales'), 0, 0, 'L');
        
        // Identificador
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(71, 85, 105);
        if (!empty($this->documentSubTitle)) {
            $this->Cell(0, 5, $this->decode($this->documentSubTitle), 0, 1, 'R');
        } else {
            $this->Ln(5);
        }
        
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 5, $this->decode('Av. Principal #123, Santa Cruz, Bolivia'), 0, 1, 'L');
        $this->Cell(0, 5, 'Tel: +591 71234567 | NIT: 1029384756', 0, 1, 'L');
        
        // Línea divisoria decorativa
        $this->Ln(3);
        $this->SetDrawColor(226, 232, 240); // Gris muy claro (#e2e8f0)
        $this->SetLineWidth(0.5);
        $this->Line($this->GetX(), $this->GetY(), $this->GetPageWidth() - $this->GetX(), $this->GetY());
        $this->Ln(5);
    }

    /**
     * Sobrescribe el método Footer de FPDF para estandarizar el pie de página.
     */
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(148, 163, 184); // Gris claro (#94a3b8)
        
        // Fecha y hora actual
        $fecha = date('d/m/Y H:i:s');
        $this->Cell(100, 10, $this->decode('Impreso el: ' . $fecha), 0, 0, 'L');
        
        // Paginación dinámica
        $this->Cell(0, 10, $this->decode('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    /**
     * Renderiza una sección con título de bloque estilizado.
     * 
     * @param string $title Título del bloque.
     */
    public function renderSectionHeader($title) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(30, 41, 59);
        $this->SetFillColor(248, 250, 252); // Fondo extremadamente suave (#f8fafc)
        $this->Cell(0, 6, $this->decode($title), 0, 1, 'L', true);
        $this->Ln(2);
    }

    /**
     * Dibuja una tabla estilizada para los datos de desglose.
     * 
     * @param array $header Títulos de columna.
     * @param array $data Filas con valores.
     * @param array $widths Anchos proporcionales de columna.
     * @param array $aligns Alineación de columnas ('L', 'C', 'R').
     */
    public function renderTable(array $header, array $data, array $widths, array $aligns = []) {
        // Configuración de fuentes y colores de cabecera
        $this->SetFillColor(241, 245, 249); // Color gris claro de fondo (#f1f5f9)
        $this->SetTextColor(51, 65, 85); // Oscuro (#334155)
        $this->SetDrawColor(226, 232, 240); // Línea (#e2e8f0)
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 9);
        
        // Renderizar Cabecera
        for ($i = 0; $i < count($header); $i++) {
            $align = isset($aligns[$i]) ? $aligns[$i] : 'L';
            $this->Cell($widths[$i], 7, $this->decode($header[$i]), 1, 0, $align, true);
        }
        $this->Ln();
        
        // Renderizar Filas de Datos
        $this->SetTextColor(71, 85, 105); // Gris (#475569)
        $this->SetFont('Arial', '', 9);
        
        foreach ($data as $row) {
            for ($i = 0; $i < count($row); $i++) {
                $align = isset($aligns[$i]) ? $aligns[$i] : 'L';
                $this->Cell($widths[$i], 6, $this->decode($row[$i]), 1, 0, $align);
            }
            $this->Ln();
        }
        $this->Ln(3);
    }
}
