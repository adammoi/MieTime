<?php

/**
 * Mie Time - Authentication Helper
 * Fungsi-fungsi untuk cek autentikasi dan role
 */

if (!defined('MIE_TIME')) {
    die('Direct access not permitted');
}

/**
 * Require login - redirect jika belum login
 */
function require_login($redirect_to = null)
{
    if (!is_logged_in()) {
        $redirect_url = $redirect_to ?? $_SERVER['REQUEST_URI'];
        set_flash('warning', 'Anda harus login terlebih dahulu');
        redirect('login?redirect=' . urlencode($redirect_url));
    }
}

/**
 * Require specific role
 */
function require_role($role)
{
    require_login();

    if (!has_role($role)) {
        set_flash('error', 'Anda tidak memiliki akses ke halaman ini');
        redirect('');
    }
}

/**
 * Require admin or moderator
 */
function require_admin_or_moderator()
{
    require_login();

    if (!is_admin_or_moderator()) {
        set_flash('error', 'Akses ditolak. Hanya admin/moderator yang diizinkan');
        redirect('');
    }
}

/**
 * Require admin only
 */
function require_admin()
{
    require_login();

    if (!has_role('admin')) {
        set_flash('error', 'Akses ditolak. Hanya admin yang diizinkan');
        redirect('');
    }
}

/**
 * Check if user owns the resource
 */
function owns_resource($user_id)
{
    return is_logged_in() && get_current_user_id() == $user_id;
}

/**
 * Check if user can edit resource (owner or admin/moderator)
 */
function can_edit_resource($user_id)
{
    return owns_resource($user_id) || is_admin_or_moderator();
}

/**
 * Check if user is verified owner of location
 */
function is_verified_owner_of($location_id)
{
    if (!is_logged_in() || !has_role('verified_owner')) {
        return false;
    }

    $location = get_location_by_id($location_id);
    return $location && $location['owner_user_id'] == get_current_user_id();
}

/**
 * Get user role badge HTML
 */
function get_role_badge($role)
{
    $badges = [
        'admin' => '<span class="badge bg-danger"><i class="fas fa-shield-alt me-1"></i>Admin</span>',
        'moderator' => '<span class="badge bg-warning text-dark"><i class="fas fa-user-shield me-1"></i>Moderator</span>',
        'verified_owner' => '<span class="badge bg-success"><i class="fas fa-certificate me-1"></i>Verified Owner</span>',
        'contributor' => '<span class="badge bg-primary"><i class="fas fa-user me-1"></i>Contributor</span>'
    ];

    return $badges[$role] ?? '<span class="badge bg-secondary"><i class="fas fa-user me-1"></i>User</span>';
}

/**
 * Check if action requires login (for guest soft wall)
 */
function action_requires_login($action)
{
    $protected_actions = [
        'review',
        'vote',
        'bookmark',
        'claim',
        'add_location',
        'edit',
        'delete',
        'report'
    ];

    return in_array($action, $protected_actions);
}

/**
 * Generate login modal trigger
 * Digunakan untuk guest yang mencoba melakukan aksi
 */
function login_modal_trigger($action_text = 'melakukan aksi ini')
{
    if (is_logged_in()) {
        return '';
    }

    return "onclick=\"event.preventDefault(); showLoginModal('$action_text'); return false;\"";
}
