jQuery(document).ready(function ($) {

    // --------------------------------------------------------------------------
    // 1. Uploader de Imagem (Capa Vertical)
    // --------------------------------------------------------------------------
    var mediaUploader;

    $('#btn_upload_capa').on('click', function (e) {
        e.preventDefault();

        // Se já existe uma instância do uploader, abre ela.
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Cria a instância do media uploader
        mediaUploader = wp.media({
            title: 'Escolher Capa Vertical',
            button: {
                text: 'Usar esta imagem'
            },
            multiple: false
        });

        // Quando uma imagem é selecionada, roda o callback
        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#capa_vertical_input').val(attachment.id);
            $('#capa_vertical_preview').attr('src', attachment.url).show();
            $('#btn_remove_capa').show();
        });

        // Abre o modal
        mediaUploader.open();
    });

    $('#btn_remove_capa').on('click', function (e) {
        e.preventDefault();
        $('#capa_vertical_input').val('');
        $('#capa_vertical_preview').attr('src', '').hide();
        $(this).hide();
    });


    // --------------------------------------------------------------------------
    // 2. Repeater de Arquivos (Materiais de Apoio)
    // --------------------------------------------------------------------------
    var fileUploader;

    // Adicionar Linha
    $('#btn_add_arquivo_row').on('click', function (e) {
        e.preventDefault();
        var index = $('#arquivos_repeater_list .repeater-item').length;
        var template = $('#tmpl-arquivo-row').html();
        template = template.replace(/INDEX/g, index);
        $('#arquivos_repeater_list').append(template);
    });

    // Remover Linha
    $(document).on('click', '.btn-remove-row', function (e) {
        e.preventDefault();
        if (confirm('Tem certeza que deseja remover este item?')) {
            $(this).closest('.repeater-item').remove();
            // Reindexar inputs para evitar buracos (opcional, simples basta salvar)
        }
    });

    // Upload de Arquivo na Linha
    $(document).on('click', '.btn-upload-file', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $input = $button.siblings('.file-url-input');

        // Se quisermos apenas reabrir o frame global, ok. 
        // Mas o ideal é criar uma instancia nova ou setar o callback dinamicamente.
        // Vamos usar uma instancia dinâmica.
        var customUploader = wp.media.frames.file_frame = wp.media({
            title: 'Escolher Arquivo',
            button: { text: 'Usar este arquivo' }
        });

        customUploader.on('select', function () {
            var attachment = customUploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
        });

        customUploader.open();
    });

});
