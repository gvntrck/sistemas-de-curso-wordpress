<?php
/**
 * Plugin Name: Sistema de Cursos Personalizado
 * Description: Plugin que unifica todos os snippets do sistema de cursos (Cadastro, Certificados, Aulas, Trilhas, Controle de Acesso, etc) em um único local.
 * Version: 1.0.0
 * Author: Equipe de Desenvolvimento
 * Text Domain: sistema-cursos
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Array com os nomes dos arquivos (snippets) que compõem o sistema.
// A ordem pode importar se houver dependências entre eles, mas snippets costumam ser independentes ou hook-based.
$arquivos_snippets = [
    'barra-progresso-geral.php',
    'cadastro-usuario.php',
    'campos-usuario.php',
    'certificado.php',
    'controle-acesso.php',
    'cursos-trilha.php',
    'filtro-listagem-aulas.php',
    'listar-aulas.php',
    'meus-cursos.php',
    'minha-conta.php',
    'resultado-busca.php',
    'single-aula.php',
    'single-trilha.php',
];

foreach ($arquivos_snippets as $arquivo) {
    $caminho_arquivo = plugin_dir_path(__FILE__) . $arquivo;

    if (file_exists($caminho_arquivo)) {
        require_once $caminho_arquivo;
    } else {
        // Opcional: Logar erro se arquivo faltar
        error_log("Sistema de Cursos: Arquivo não encontrado - " . $arquivo);
    }
}
