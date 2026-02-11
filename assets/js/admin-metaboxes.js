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

    // --------------------------------------------------------------------------
    // 3. Filtro do metabox "Gerenciar Aulas do Curso"
    // --------------------------------------------------------------------------
    function updateCourseLessonsFilter() {
        var $list = $('#curso_aulas_manager_list');
        if (!$list.length) {
            return;
        }

        var selectedFilter = $('input[name="curso_aulas_filter_view"]:checked').val() || 'course';
        var visibleCount = 0;

        $list.find('.sc-aula-item').each(function () {
            var $item = $(this);
            var isInCourse = String($item.attr('data-in-course')) === '1';
            var shouldShow = selectedFilter === 'all' || isInCourse;

            $item.toggle(shouldShow);
            if (shouldShow) {
                visibleCount += 1;
            }
        });

        $('#curso_aulas_manager_empty_filter').toggle(visibleCount === 0);
    }

    $(document).on('change', 'input[name="curso_aulas_filter_view"]', updateCourseLessonsFilter);
    updateCourseLessonsFilter();

    // --------------------------------------------------------------------------
    // 4. Criacao rapida de aulas na tela do curso
    // --------------------------------------------------------------------------
    function addQuickLessonRow() {
        var template = $('#tmpl-curso-nova-aula-row').html();
        if (!template) {
            return;
        }

        $('#curso_novas_aulas_list').append(template);
        $('#curso_novas_aulas_list .sc-nova-aula-row:last-child input[type="text"]').trigger('focus');
    }

    $('#btn_add_nova_aula_row').on('click', function (e) {
        e.preventDefault();
        addQuickLessonRow();
    });

    $(document).on('click', '.btn-remove-nova-aula-row', function (e) {
        e.preventDefault();

        var $rows = $('#curso_novas_aulas_list .sc-nova-aula-row');
        if ($rows.length <= 1) {
            $rows.find('input[type="text"]').val('');
            return;
        }

        $(this).closest('.sc-nova-aula-row').remove();
    });

});
