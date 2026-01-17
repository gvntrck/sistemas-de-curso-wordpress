<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Config
{
    /**
     * class-config.php
     *
     * Armazena configurações centralizadas e mensagens padrão do sistema.
     * Útil para manter consistência em textos e valores reutilizáveis em todo o plugin.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    /**
     * Retorna mensagens padrão do sistema.
     * Pode ser expandido para pegar de opções do banco de dados no futuro.
     */
    public static function get_message($key)
    {
        $messages = [
            'access_denied' => '<div class="mc-alert mc-error">
                Você precisa estar logado para ver esta página. <a href="' . wp_login_url(get_permalink()) . '" style="color: inherit; text-decoration: underline;">Fazer login</a>
            </div>',
            'not_enrolled' => '<div class="mc-alert mc-error">
                Você não tem permissão para acessar este curso.
            </div>',
            'save_success' => '<div class="mc-alert mc-success">Dados atualizados com sucesso!</div>',
            'save_error' => '<div class="mc-alert mc-error">Ocorreu um erro ao salvar os dados. Tente novamente.</div>',
        ];

        return isset($messages[$key]) ? $messages[$key] : '';
    }

    /**
     * Retorna cores padrão (se precisarmos injetar inline ou algo assim, 
     * embora o ideal seja usar variáveis CSS).
     */
    public static function get_colors()
    {
        return [
            'primary' => '#FDC110',
            'bg' => '#121212',
        ];
    }
}
