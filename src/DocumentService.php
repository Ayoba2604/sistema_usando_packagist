<?php

declare(strict_types=1);

namespace App;

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

final class DocumentService
{
    public function __construct(private readonly string $storagePath)
    {
    }

    /** @param array{name: string, email: string, amount: string, description: string} $data
     *  @return array{pdf: string, qr: string, code: string}
     */
    public function create(array $data): array
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }

        $code = 'DOC-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $safeCode = preg_replace('/[^A-Z0-9-]/', '', $code) ?: 'documento';
        $qrPath = $this->storagePath . DIRECTORY_SEPARATOR . $safeCode . '.png';
        $pdfPath = $this->storagePath . DIRECTORY_SEPARATOR . $safeCode . '.pdf';

        $qrCode = new QrCode(
            data: 'Código de verificação: ' . $code,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 10,
        );
        $qr = (new PngWriter())->write($qrCode);
        $qr->saveToFile($qrPath);

        $html = $this->renderHtml($data, $code, base64_encode((string) file_get_contents($qrPath)));
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();
        file_put_contents($pdfPath, $dompdf->output());

        return ['pdf' => $pdfPath, 'qr' => $qrPath, 'code' => $code];
    }

    /** @param array{name: string, email: string, amount: string, description: string} $data */
    private function renderHtml(array $data, string $code, string $qrBase64): string
    {
        $name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
        $amount = htmlspecialchars($data['amount'], ENT_QUOTES, 'UTF-8');
        $description = nl2br(htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8'));
        $date = date('d/m/Y H:i');

        return <<<HTML
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><style>
body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 13px; }
.header { background: #1d4ed8; color: #fff; padding: 25px; }
.header h1 { margin: 0; font-size: 25px; }
.box { margin: 28px 25px; border: 1px solid #d1d5db; padding: 20px; border-radius: 6px; }
table { width: 100%; border-collapse: collapse; } td { padding: 9px; border-bottom: 1px solid #e5e7eb; }
td:first-child { font-weight: bold; width: 35%; } .code { color: #1d4ed8; font-weight: bold; }
.qr { text-align: center; margin-top: 25px; } .footer { text-align: center; color: #6b7280; font-size: 10px; }
</style></head><body>
<div class="header"><h1>Comprovante de Solicitação</h1><p>Emitido em {$date}</p></div>
<div class="box"><table>
<tr><td>Beneficiário</td><td>{$name}</td></tr><tr><td>E-mail</td><td>{$email}</td></tr>
<tr><td>Valor</td><td>R$ {$amount}</td></tr><tr><td>Descrição</td><td>{$description}</td></tr>
<tr><td>Código</td><td class="code">{$code}</td></tr>
</table><div class="qr"><img src="data:image/png;base64,{$qrBase64}" width="160"><p>Use o QR Code para validar o código do documento.</p></div></div>
<p class="footer">Documento gerado automaticamente pelo Sistema de Documentos PHP.</p>
</body></html>
HTML;
    }
}
