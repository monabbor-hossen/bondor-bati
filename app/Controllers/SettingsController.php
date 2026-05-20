<?php
namespace App\Controllers;

use App\Core\Controller;

class SettingsController extends Controller {
    public function index() {
        $this->view('settings/index', [
            'pageTitle' => __('settings_title'),
            'activeNav' => 'settings'
        ]);
    }
}
