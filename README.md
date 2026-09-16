# Sistema de Documentos PHP

Aplicacao demonstrativa que utiliza tres pacotes do Packagist:

- `dompdf/dompdf`: cria o comprovante em PDF;
- `endroid/qr-code`: gera um QR Code de verificacao;
- `phpmailer/phpmailer`: envia o documento por SMTP.

## Instalação

No Windows, execute `instalar_dependencias.bat`. O script usa o Composer instalado ou baixa um `composer.phar` local e instala todas as dependencias.

Depois, copie `.env.example` para `.env` e preencha as credenciais SMTP caso queira enviar e-mails. Não envie credenciais reais para o repositório.

Abra `index.php` em um servidor com PHP 8.1 ou superior, por exemplo:

```bash
php -S localhost:8000
```

Então acesse `http://localhost:8000`.
# sistema_usando_packagist
