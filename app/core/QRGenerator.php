<?php
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QRGenerator {
    public static function generate(string $payload, string $filePath): bool {
        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($payload)
                ->size(300)
                ->margin(10)
                ->build();
            $result->saveToFile($filePath);
            return true;
        } catch (Exception $e) {
            error_log("QR error: " . $e->getMessage());
            return false;
        }
    }
}
