<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;

class SettingsController extends Controller {
    public function index() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('?url=bazaar');
        }

        $this->view('settings/index', [
            'pageTitle' => __('settings_title'),
            'activeNav' => 'settings'
        ]);
    }

    public function priceCalculator() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('?url=bazaar');
        }

        $db = (new Database())->getConnection();
        $rawItems = $db->query("SELECT id, item_name, avg_unit_price, unit FROM raw_inventory ORDER BY item_name")->fetchAll();

        $this->view('settings/calculator', [
            'pageTitle' => __('price_calculator'),
            'activeNav' => 'settings',
            'rawItems'  => $rawItems,
        ]);
    }
}
