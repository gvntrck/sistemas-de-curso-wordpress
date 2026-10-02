<?php
// Executar: php .context/tests/trilha-matriculas.php
// Verifica as classes reais com substitutos em memória para as APIs do WordPress.
define('ABSPATH', __DIR__);
$posts = [
    10 => ['type' => 'trilha', 'status' => 'publish', 'title' => 'Trilha A'],
    11 => ['type' => 'trilha', 'status' => 'publish', 'title' => 'Trilha B'],
    20 => ['type' => 'curso', 'status' => 'publish', 'title' => 'Curso A'],
    21 => ['type' => 'curso', 'status' => 'publish', 'title' => 'Curso B'],
    30 => ['type' => 'grupo', 'status' => 'publish', 'title' => 'Grupo A'],
];
$meta = [20 => ['trilha' => [10]], 21 => ['trilha' => [11]]];
$user_meta = [];
$users = [1 => (object) ['ID' => 1, 'display_name' => 'Ana', 'user_email' => 'ana@example.test']];
$can_manage = true;
$can_edit = true;
$revision = false;
$boxes = [];
function add_action(...$args) {}
function add_meta_box($id, $title, $callback, $screen, ...$args) { global $boxes; $boxes[$id] = [$title, $callback, $screen]; }
function get_post_type($id) { global $posts; return $posts[$id]['type'] ?? false; }
function get_post_status($id) { global $posts; return $posts[$id]['status'] ?? false; }
function get_the_title($id) { global $posts; return $posts[$id]['title'] ?? ''; }
function get_post_meta($id, $key, $single = false) { global $meta; $values = $meta[$id][$key] ?? []; return $single ? ($values[0] ?? '') : $values; }
function update_post_meta($id, $key, $value) { global $meta; $meta[$id][$key] = [$value]; }
function add_post_meta($id, $key, $value) { global $meta; $meta[$id][$key][] = $value; }
function delete_post_meta($id, $key, $value = null) {
    global $meta;
    if ($value === null) { unset($meta[$id][$key]); return; }
    $meta[$id][$key] = array_values(array_filter($meta[$id][$key] ?? [], fn($v) => $v != $value));
}
function get_user_meta($id, $key, $single = false) { global $user_meta; return $user_meta[$id][$key] ?? ''; }
function get_user_by($field, $id) { global $users; return $users[$id] ?? false; }
function get_users($args) { global $users; return array_values($users); }
function current_user_can($capability, ...$args) { global $can_manage, $can_edit; return $capability === 'manage_options' ? $can_manage : $can_edit; }
function wp_is_post_revision($id) { global $revision; return $revision; }
function wp_verify_nonce($nonce, $action) { return $nonce === $action; }
function wp_unslash($value) { return $value; }
function absint($value) { return abs((int) $value); }
function sanitize_text_field($value) { return trim($value); }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES); }
function esc_html($value) { return esc_attr($value); }
function wp_nonce_field($action, $name) { echo '<input name="' . $name . '" value="' . $action . '">'; }
function wp_enqueue_script(...$args) {}
function get_posts($args) {
    global $posts;
    $ids = [];
    foreach ($posts as $id => $post) {
        if (!in_array($post['type'], (array) $args['post_type'], true) || $post['status'] !== ($args['post_status'] ?? 'publish')) { continue; }
        if (isset($args['meta_key']) && !in_array($args['meta_value'], get_post_meta($id, $args['meta_key']), false)) { continue; }
        foreach ($args['meta_query'] ?? [] as $query) {
            if (!array_intersect(get_post_meta($id, $query['key']), (array) $query['value'])) { continue 2; }
        }
        $ids[] = $id;
    }
    return $ids;
}
$wpdb = new class {
    public $prefix = 'wp_';
    public $direct = [];
    public function prepare($query, ...$values) { return $values; }
    public function get_row($values) { return $this->direct[$values[0]][$values[1]] ?? null; }
    public function get_col($values) {
        return array_keys(array_filter($this->direct[$values[0]] ?? [], fn($row) => $row->status === 'ativo' && ($row->data_fim === null || strtotime($row->data_fim) >= time())));
    }
};
require dirname(__DIR__, 2) . '/includes/class-access-control.php';
require dirname(__DIR__, 2) . '/includes/class-cpt-manager.php';
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
function submit_students($ids) {
    global $manager;
    $_POST = ['sistema_cursos_nonce' => 'sistema_cursos_save_meta', 'trilha_alunos_nonce' => 'trilha_alunos_save', 'trilha_alunos' => $ids, 'trilha_cursos' => [20]];
    $manager->save_metaboxes(10);
}
$manager = new System_Cursos_CPT_Manager();
$manager->add_metaboxes();
check($boxes['trilha_alunos_manager'][2] === 'trilha', 'Metabox ausente na trilha.');
submit_students(['1', '1', '999', '0']);
check(get_post_meta(10, '_trilha_aluno') === [1], 'Matrícula deve validar usuários e eliminar duplicatas.');
check(System_Cursos_Access_Control::get_access_source(1, 20)['type'] === 'trilha', 'Acesso pela trilha ausente.');
check(!System_Cursos_Access_Control::has_access(1, 21), 'Outra trilha não deve conceder acesso.');
check(!System_Cursos_Access_Control::has_access(2, 20), 'Aluno não matriculado não deve ter acesso.');
check(System_Cursos_Access_Control::get_user_courses(1) === [20], 'Curso da trilha deve aparecer em Meus Cursos.');
ob_start();
$manager->render_curso_alunos_metabox((object) ['ID' => 10, 'post_type' => 'trilha']);
$html = ob_get_clean();
check(str_contains($html, 'name="trilha_alunos[]"') && str_contains($html, 'value="1" data-search="ana ana@example.test" selected="selected"'), 'Seletor deve mostrar a matrícula salva.');
foreach (['nonce', 'permissão', 'edição', 'revisão', 'formato', 'aninhado'] as $case) {
    $_POST = ['sistema_cursos_nonce' => 'sistema_cursos_save_meta', 'trilha_alunos_nonce' => 'trilha_alunos_save', 'trilha_alunos' => [], 'trilha_cursos' => [20]];
    if ($case === 'nonce') { unset($_POST['trilha_alunos_nonce']); }
    if ($case === 'permissão') { $can_manage = false; }
    if ($case === 'edição') { $can_edit = false; }
    if ($case === 'revisão') { $revision = true; }
    if ($case === 'formato') { $_POST['trilha_alunos'] = '1'; }
    if ($case === 'aninhado') { $_POST['trilha_alunos'] = [[1]]; }
    $manager->save_metaboxes(10);
    check(get_post_meta(10, '_trilha_aluno') === [1], 'Matrícula alterada sem proteção: ' . $case);
    $can_manage = $can_edit = true;
    $revision = false;
}
update_post_meta(21, 'trilha', 10);
check(System_Cursos_Access_Control::has_access(1, 21), 'Curso adicionado depois deve herdar acesso.');
check(System_Cursos_Access_Control::get_user_courses(1) === [20, 21], 'Curso novo deve aparecer em Meus Cursos.');
update_post_meta(21, 'trilha', 11);
check(!System_Cursos_Access_Control::has_access(1, 21) && System_Cursos_Access_Control::get_user_courses(1) === [20], 'Curso movido para outra trilha deve perder o acesso herdado.');
update_post_meta(21, 'trilha', 10);
$posts[10]['status'] = 'trash';
check(!System_Cursos_Access_Control::has_access(1, 20) && System_Cursos_Access_Control::get_user_courses(1) === [], 'Trilha excluída não deve liberar cursos.');
$posts[10]['status'] = 'publish';
$wpdb->direct[1][20] = (object) ['status' => 'ativo', 'data_fim' => null];
$user_meta[1]['_aluno_grupos'] = [30];
update_post_meta(21, '_grupos_permitidos', [30]);
submit_students([]);
check(get_post_meta(10, '_trilha_aluno') === [], 'Remover todos deve limpar as matrículas.');
check(System_Cursos_Access_Control::get_access_source(1, 20)['type'] === 'direct', 'Remoção não deve revogar matrícula direta.');
check(System_Cursos_Access_Control::get_access_source(1, 21)['type'] === 'group', 'Remoção não deve revogar acesso por grupo.');
unset($wpdb->direct[1][20], $user_meta[1]);
check(!System_Cursos_Access_Control::has_access(1, 20), 'Remoção deve encerrar acesso concedido somente pela trilha.');
echo "OK: matrícula, seletor, permissões, cursos novos, exclusão e acessos independentes.\n";
