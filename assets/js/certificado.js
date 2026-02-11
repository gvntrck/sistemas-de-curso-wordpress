function imprimirCertificado() {
    var certElement = document.getElementById('printable-cert');
    if (!certElement) {
        alert('Erro: Certificado não encontrado.');
        return;
    }
    // Obtém a fonte configurada do atributo data
    var fontFamily = certElement.getAttribute('data-font') || 'Roboto';
    var fontUrl = certElement.getAttribute('data-font-url') || '';
    var filename = certElement.getAttribute('data-filename') || 'Certificado';
    // Cria uma nova janela para impressão
    var printWindow = window.open('', '_blank', 'width=1200,height=800');
    // Estilos inline para a janela de impressão (incluindo a fonte)
    var estilos = `
    <link href="${fontUrl}" rel="stylesheet">
    <style>
        * {margin: 0; padding: 0; box-sizing: border-box; }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: white;
        }
        .cert-container {
            position: relative;
            width: 100%;
            max-width: 1000px;
            aspect-ratio: 1.414 / 1;
        }
        .cert-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .cert-element {
            position: absolute;
            font-weight: bold;
            white-space: nowrap;
        }
        .cert-element.cert-align-center {
            transform: translateX(-50%);
            text-align: center;
        }
        .cert-element.cert-align-left {
            transform: none;
            text-align: left;
        }
        .cert-element.cert-align-right {
            transform: translateX(-100%);
            text-align: right;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .cert-container {
                max-width: none;
                width: 100vw;
                height: 100vh;
            }
            .cert-bg {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page {
            size: landscape;
            margin: 0;
        }
    </style>
    `;

    // Monta o HTML da janela de impressão
    printWindow.document.write('<!DOCTYPE html><html><head><title>' + filename + '</title>' + estilos + '</head><body>');
    printWindow.document.write(certElement.outerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();

    // Aguarda a fonte e imagem carregar antes de imprimir
    printWindow.onload = function () {
        setTimeout(function () {
            printWindow.focus();
            printWindow.print();
        }, 800); // Aumentado para dar tempo da fonte carregar
    };
}
