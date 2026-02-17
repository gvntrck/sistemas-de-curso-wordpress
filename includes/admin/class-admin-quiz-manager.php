<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Admin_Quiz_Manager
{
    /**
     * class-admin-quiz-manager.php
     *
     * Gerencia tentativas de quiz no perfil do usuário (Admin).
     *
     * @package SistemaCursos
     * @version 1.0.0
     */
    public function __construct()
    {
        add_action('show_user_profile', [$this, 'render_quiz_attempts_section']);
        add_action('edit_user_profile', [$this, 'render_quiz_attempts_section']);
        
        add_action('personal_options_update', [$this, 'save_quiz_attempts_reset']);
        add_action('edit_user_profile_update', [$this, 'save_quiz_attempts_reset']);
    }

    /**
     * Renderiza a seção de gestão de tentativas no perfil do usuário.
     */
    public function render_quiz_attempts_section($user)
    {
        if (!current_user_can('edit_users')) {
            return;
        }

        // Buscar aulas onde o usuário tem tentativas registradas
        $lessons_with_attempts = $this->get_user_quiz_activity($user->ID);
        ?>
        <div class="sc-admin-section">
            <h3>📝 Gestão de Tentativas de Quiz</h3>
            <p class="description">Abaixo estão listadas as aulas onde este usuário já realizou tentativas de quiz.</p>
            
            <table class="widefat fixed striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Aula / Curso</th>
                        <th>Tentativas Usadas</th>
                        <th>Máx. Permitido</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lessons_with_attempts)): ?>
                        <tr>
                            <td colspan="5">Nenhuma atividade de quiz registrada para este usuário.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lessons_with_attempts as $activity): 
                            $is_blocked = ($activity['max_attempts'] > 0 && $activity['used_attempts'] >= $activity['max_attempts']);
                            $status_class = $is_blocked ? 'color: #d63638; font-weight: bold;' : 'color: #00a32a;';
                            $status_text = $is_blocked ? 'Bloqueado 🚫' : 'Liberado ✅';
                            
                            if ($activity['passed']) {
                                $status_text = 'Aprovado 🎉';
                                $status_class = 'color: #00a32a; font-weight: bold;';
                            }
                        ?>
                            <tr>
                                <td>
                                    <strong><a href="<?php echo get_edit_post_link($activity['aula_id']); ?>" target="_blank"><?php echo esc_html($activity['aula_title']); ?></a></strong>
                                    <br>
                                    <span class="description">Curso: <?php echo esc_html($activity['curso_title']); ?></span>
                                </td>
                                <td><?php echo $activity['used_attempts']; ?></td>
                                <td><?php echo ($activity['max_attempts'] > 0) ? $activity['max_attempts'] : 'Ilimitado'; ?></td>
                                <td style="<?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                    <?php if ($activity['passed']): ?>
                                        <br><span class="description">Nota: <?php echo $activity['score']; ?>%</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($activity['used_attempts'] > 0): ?>
                                        <label>
                                            <input type="checkbox" name="reset_quiz_attempts[<?php echo $activity['aula_id']; ?>]" value="1">
                                            Resetar Tentativas
                                        </label>
                                    <?php else: ?>
                                        <span class="description">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p class="description">Marque a caixa "Resetar Tentativas" e salve o perfil para zerar o contador de tentativas da aula específica.</p>
        </div>
        <?php
    }

    /**
     * Busca dados de atividade de quiz do usuário.
     */
    private function get_user_quiz_activity($user_id)
    {
        global $wpdb;
        $activities = [];

        // Buscar todos os meta keys que começam com _quiz_attempts_
        $meta_key_prefix = '_quiz_attempts_';
        
        // Infelizmente get_user_meta não suporta LIKE na key facilmente sem custom query
        $query = $wpdb->prepare(
            "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s",
            $user_id,
            $meta_key_prefix . '%'
        );
        
        $results = $wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        foreach ($results as $row) {
            $aula_id = str_replace($meta_key_prefix, '', $row->meta_key);
            $used_attempts = intval($row->meta_value);
            
            if ($aula_id <= 0) continue;

            $aula = get_post($aula_id);
            if (!$aula || $aula->post_type !== 'aula') continue;

            // Dados do Quiz
            $quiz_data = get_post_meta($aula_id, '_aula_quiz_data', true);
            $max_attempts = isset($quiz_data['max_attempts']) ? intval($quiz_data['max_attempts']) : 0;
            
            // Dados de Conclusão (se houver)
            $passed = false;
            $score = 0;
            if (class_exists('System_Cursos_Quiz_Process')) {
                 $passed = System_Cursos_Quiz_Process::user_passed($user_id, $aula_id);
            }
            
            // Buscar nota se passou
            if ($passed) {
                $table_name = $wpdb->prefix . 'progresso_aluno';
                $progress = $wpdb->get_row($wpdb->prepare(
                    "SELECT pontuacao FROM $table_name WHERE user_id = %d AND aula_id = %d",
                    $user_id, $aula_id
                ));
                if ($progress) {
                    $score = $progress->pontuacao;
                }
            }
            
            $curso_id = get_post_meta($aula_id, 'curso', true);
            $curso_title = $curso_id ? get_the_title($curso_id) : 'Sem Curso';

            $activities[] = [
                'aula_id' => $aula_id,
                'aula_title' => $aula->post_title,
                'curso_title' => $curso_title,
                'used_attempts' => $used_attempts,
                'max_attempts' => $max_attempts,
                'passed' => $passed,
                'score' => $score
            ];
        }

        return $activities;
    }

    /**
     * Salva o reset de tentativas.
     */
    public function save_quiz_attempts_reset($user_id)
    {
        if (!current_user_can('edit_users')) {
            return;
        }

        // Verifica nonce padrão do WordPress para update de perfil, se necessário, ou confia que o WP já checou.
        // O hook personal_options_update roda após verificação de nonce do WP.

        if (empty($_POST['reset_quiz_attempts']) || !is_array($_POST['reset_quiz_attempts'])) {
            return;
        }

        foreach ($_POST['reset_quiz_attempts'] as $aula_id => $reset) {
            if ($reset == '1') {
                $aula_id = intval($aula_id);
                // Resetar tentativas: remover a meta ou setar para 0
                update_user_meta($user_id, '_quiz_attempts_' . $aula_id, 0);
            }
        }
    }
}
