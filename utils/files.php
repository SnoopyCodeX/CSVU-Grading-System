<?php

use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser;

/**
 * Validates a PDF file 
 * by trying to parse its contents. Returns `true`
 * if it succeeds without exception. Otherwise, `false`.
 *
 * @param string $pdfFile The file path of the pdf file
 * @return bool
 */
function validatePDFFile($pdfFile) {
    try {
        // The free parser that comes with
        // FPDI only supports up to v1.4.
        // PDF Files version >=1.5 would cause
        // FPDI library to throw an exception.
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->setSourceFile($pdfFile);
        $pdf->cleanUp(true);

        return true;
    } catch (Exception $e1) {

        // If error message contains 'https://www.setasign.com/fpdi-pdf-parser',
        // this means that the pdf file is probably higher than version 1.4.
        if (str_contains(strtolower($e1->getMessage()), "https://www.setasign.com/fpdi-pdf-parser")) {
            // If it fails using FPDI, we'll use Smalot\PDFParser library 
            // to try and parse the pdf file.
            try {
                $parser = new Parser();
                $parser->parseFile($pdfFile);

                return true;
            } catch (Exception $e2) {}
        }

    }

    return false;
}

?>