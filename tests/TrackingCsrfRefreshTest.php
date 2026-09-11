<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

final class TrackingCsrfRefreshTest extends TestCase
{
    public function testTrackingDrawerRefreshesExpiredCsrfTokenBeforePosting(): void
    {
        $controller = file_get_contents(ROOTPATH . 'app/Controllers/TrackingController.php');
        $view = file_get_contents(ROOTPATH . 'app/Views/logistics/pod_tracking_drawer.php');

        self::assertStringContainsString("'csrf_hash' => csrf_hash()", $controller);
        self::assertStringContainsString('refreshTrackingCsrf(response.csrf_hash);', $view);
        self::assertStringContainsString('refreshTrackingCsrf();', $view);
        self::assertStringNotContainsString("'<?= csrf_token() ?>': '<?= csrf_hash() ?>'", $view);
    }

    public function testWordPressPluginReadsFromProductionErp(): void
    {
        $plugin = file_get_contents(ROOTPATH . 'wp-plugin/ma-logistics-tracking/ma-logistics-tracking.php');

        self::assertStringContainsString('https://erp.malogistics.co.in/api/track/', $plugin);
        self::assertStringNotContainsString('https://granthinfotech.online/api/track/', $plugin);
        self::assertStringContainsString("do_action('litespeed_purge_all')", $plugin);
    }

    public function testWordPressPublicTrackingApiContractRemainsAvailable(): void
    {
        $routes = file_get_contents(ROOTPATH . 'app/Config/Routes.php');
        $filters = file_get_contents(ROOTPATH . 'app/Config/Filters.php');
        $controller = file_get_contents(ROOTPATH . 'app/Controllers/TrackingController.php');
        $plugin = file_get_contents(ROOTPATH . 'wp-plugin/ma-logistics-tracking/assets/js/ma-tracking.js');

        self::assertStringContainsString("\$routes->get('api/track/(:any)', 'TrackingController::trackByAwb/\$1');", $routes);
        self::assertStringContainsString("'api/track/*'", $filters);
        self::assertStringContainsString('Access-Control-Allow-Origin: *', $controller);
        self::assertStringContainsString("'status' => 'success'", $controller);
        self::assertStringContainsString("'booking' => \$formattedBooking", $controller);
        self::assertStringContainsString("'history' => \$formattedHistory", $controller);

        foreach (['awb_no', 'current_status', 'booking_date', 'consignor_name', 'consignee_name', 'origin', 'destination', 'total_pieces'] as $field) {
            self::assertStringContainsString("'{$field}'", $controller);
            self::assertStringContainsString("b.{$field}", $plugin);
        }
        foreach (['date', 'time', 'location', 'status', 'activity', 'remarks', 'receiver_name'] as $field) {
            self::assertStringContainsString("'{$field}'", $controller);
            self::assertStringContainsString("ev.{$field}", $plugin);
        }
    }

    public function testTrackingDatesUseDotSeparatedDisplayFormatters(): void
    {
        $drawer = file_get_contents(ROOTPATH . 'app/Views/logistics/pod_tracking_drawer.php');
        $publicPage = file_get_contents(ROOTPATH . 'app/Views/public_track.php');
        $wordpress = file_get_contents(ROOTPATH . 'wp-plugin/ma-logistics-tracking/assets/js/ma-tracking.js');

        self::assertStringContainsString('formatTrackingDate(item.event_date)', $drawer);
        self::assertStringContainsString('formatDisplayDate(row.date)', $publicPage);
        self::assertStringContainsString('formatToDotDate(ev.date)', $wordpress);
    }
}
