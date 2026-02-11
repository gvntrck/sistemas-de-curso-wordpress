<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Lesson_Schedule
{
    const RELEASE_META_KEY = '_sistema_cursos_lesson_release_at';

    /**
     * Converte input de datetime-local para formato padrao do banco.
     */
    public static function normalize_datetime_local_input($value)
    {
        $raw = is_string($value) ? trim(wp_unslash($value)) : '';
        if ($raw === '') {
            return '';
        }

        $timezone = wp_timezone();
        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $raw, $timezone);
            if ($date instanceof DateTimeImmutable) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return '';
    }

    public static function get_release_datetime($aula_id)
    {
        $aula_id = (int) $aula_id;
        if ($aula_id <= 0) {
            return '';
        }

        $stored = get_post_meta($aula_id, self::RELEASE_META_KEY, true);
        if (!is_string($stored) || trim($stored) === '') {
            return '';
        }

        return self::normalize_datetime_local_input($stored);
    }

    public static function get_release_timestamp($aula_id)
    {
        $release_datetime = self::get_release_datetime($aula_id);
        if ($release_datetime === '') {
            return 0;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $release_datetime, wp_timezone());
        if (!$date instanceof DateTimeImmutable) {
            return 0;
        }

        return (int) $date->getTimestamp();
    }

    public static function get_release_label($aula_id)
    {
        $timestamp = self::get_release_timestamp($aula_id);
        if ($timestamp <= 0) {
            return '';
        }

        return wp_date('d/m/Y H:i', $timestamp, wp_timezone());
    }

    public static function is_locked_for_user($aula_id, $user_id = 0)
    {
        $aula_id = (int) $aula_id;
        if ($aula_id <= 0) {
            return false;
        }

        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            $user_id = get_current_user_id();
        }

        if ($user_id > 0 && user_can($user_id, 'manage_options')) {
            return false;
        }

        $release_timestamp = self::get_release_timestamp($aula_id);
        if ($release_timestamp <= 0) {
            return false;
        }

        // Usar timestamp GMT/Unix para comparar com DateTimeImmutable::getTimestamp().
        $now = (int) current_time('timestamp', true);
        return $now < $release_timestamp;
    }

    public static function get_lock_message($aula_id)
    {
        $release_label = self::get_release_label($aula_id);
        if ($release_label === '') {
            return 'Esta aula ainda nao foi liberada.';
        }

        return sprintf('Esta aula sera liberada em %s.', $release_label);
    }
}
