<?php

namespace Tests;

use App\Models\CompanyModel;
use App\Models\UserCompanyAccessModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use PHPUnit\Framework\TestCase;

final class MultiCompanyAccessTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('default');
    }

    public function testRootCompanyIsMAlogisticWithIdOne(): void
    {
        $companyModel = new CompanyModel($this->db);
        $root = $companyModel->getRootCompany();

        self::assertNotNull($root, 'Root company must exist');
        self::assertSame(1, (int) $root['id'], 'Root company must have id = 1');
        self::assertSame(1, (int) ($root['is_root'] ?? 0), 'Root company must have is_root = 1');
    }

    public function testUserCanHaveIndependentRolesAndPermissionsAcrossCompanies(): void
    {
        $this->db->transStart();

        try {
            $userModel = new UserModel($this->db);
            $accessModel = new UserCompanyAccessModel($this->db);
            $companyModel = new CompanyModel($this->db);

            // Create test user
            $testUsername = 'test_multi_' . bin2hex(random_bytes(4));
            $userId = $userModel->insert([
                'username'   => $testUsername,
                'email'      => $testUsername . '@example.com',
                'password'   => 'password123',
                'role'       => 'user',
                'is_active'  => 1,
                'can_create' => 0,
                'can_edit'   => 0,
                'can_delete' => 0,
            ]);
            self::assertGreaterThan(0, $userId);

            // Create secondary test company
            $companyTwoId = $companyModel->insert([
                'name'      => 'Test Sub-Company ' . bin2hex(random_bytes(3)),
                'is_root'   => 0,
                'is_active' => 1,
            ]);
            self::assertGreaterThan(0, $companyTwoId);

            // Create third company (unassigned)
            $companyThreeId = $companyModel->insert([
                'name'      => 'Test Unassigned Company ' . bin2hex(random_bytes(3)),
                'is_root'   => 0,
                'is_active' => 1,
            ]);
            self::assertGreaterThan(0, $companyThreeId);

            // 1. Assign to Company 1 (Root) as Admin with Full CRUD
            $accessModel->assignCompany($userId, 1, [
                'role'       => 'admin',
                'can_create' => 1,
                'can_edit'   => 1,
                'can_delete' => 1,
                'is_active'  => 1,
            ]);

            // 2. Assign to Company 2 as Tracking with Zero CRUD
            $accessModel->assignCompany($userId, $companyTwoId, [
                'role'       => 'tracking',
                'can_create' => 0,
                'can_edit'   => 0,
                'can_delete' => 0,
                'is_active'  => 1,
            ]);

            // Verify independent access records
            $accessOne = $accessModel->getUserAccess($userId, 1);
            self::assertNotNull($accessOne);
            self::assertSame('admin', $accessOne['role']);
            self::assertSame(1, (int) $accessOne['can_create']);
            self::assertSame(1, (int) $accessOne['can_edit']);
            self::assertSame(1, (int) $accessOne['can_delete']);

            $accessTwo = $accessModel->getUserAccess($userId, $companyTwoId);
            self::assertNotNull($accessTwo);
            self::assertSame('tracking', $accessTwo['role']);
            self::assertSame(0, (int) $accessTwo['can_create']);
            self::assertSame(0, (int) $accessTwo['can_edit']);
            self::assertSame(0, (int) $accessTwo['can_delete']);

            // Verify company selection listing returns only assigned companies
            $assigned = $accessModel->getUserCompanies($userId, true);
            $assignedIds = array_column($assigned, 'id');
            self::assertContains(1, array_map('intval', $assignedIds));
            self::assertContains((int) $companyTwoId, array_map('intval', $assignedIds));
            self::assertNotContains((int) $companyThreeId, array_map('intval', $assignedIds), 'Unassigned company must not be returned');

            // Verify revocation of Company 2
            $revoked = $accessModel->revokeCompany($userId, $companyTwoId);
            self::assertTrue((bool) $revoked);

            $assignedAfterRevoke = $accessModel->getUserCompanies($userId, true);
            $assignedIdsAfter = array_map('intval', array_column($assignedAfterRevoke, 'id'));
            self::assertContains(1, $assignedIdsAfter);
            self::assertNotContains((int) $companyTwoId, $assignedIdsAfter);
        } finally {
            $this->db->transRollback();
        }
    }

    public function testTrackingControllerEnforcesTenantIsolationAgainstIdor(): void
    {
        $controllerSource = file_get_contents(ROOTPATH . 'app/Controllers/TrackingController.php');

        // Verify getHistory tenant check
        self::assertStringContainsString('$companyId = session()->get(\'selected_company_id\');', $controllerSource);
        self::assertStringContainsString('(int) $booking[\'company_id\'] !== (int) $companyId', $controllerSource);
        self::assertStringContainsString('setStatusCode(403)', $controllerSource);

        // Verify saveUpdate tenant check
        self::assertStringContainsString('$booking = $this->bookingModel->find($bookingId);', $controllerSource);
        self::assertStringContainsString('Unauthorized: Booking belongs to another company', $controllerSource);

        // Verify deleteUpdate tenant check
        self::assertStringContainsString('$trackingRecord = $this->trackingModel->find($id);', $controllerSource);
        self::assertStringContainsString('$booking = $bookingId ? $this->bookingModel->find($bookingId) : null;', $controllerSource);
        self::assertStringContainsString('if (!$companyId || (int) $booking[\'company_id\'] !== (int) $companyId)', $controllerSource);
    }

    public function testAuthFilterRestrictsAdminRoutesToRootCompanyAdmin(): void
    {
        $filterSource = file_get_contents(ROOTPATH . 'app/Filters/AuthFilter.php');

        self::assertStringContainsString("strpos(\$cleanUri, 'admin') === 0", $filterSource);
        self::assertStringContainsString("\$isRootCompany = session()->get('is_root_company');", $filterSource);
        self::assertStringContainsString("User Management is centralized under MA Logistic root administration.", $filterSource);
    }

    public function testAuthFilterRefreshesCompanyPermissionsOnEveryRequest(): void
    {
        $filterSource = file_get_contents(ROOTPATH . 'app/Filters/AuthFilter.php');

        self::assertStringContainsString('getUserAccess((int) session()->get(\'user_id\'), $selectedCompanyId)', $filterSource);
        self::assertStringContainsString("'can_create' => (int) \$access['can_create']", $filterSource);
        self::assertStringContainsString("'can_edit' => (int) \$access['can_edit']", $filterSource);
        self::assertStringContainsString("'can_delete' => (int) \$access['can_delete']", $filterSource);
        self::assertStringNotContainsString('TRACKING ROLE CHECK', $filterSource);
    }

    public function testV1ApiControllerRestrictsCompaniesToUserAssignments(): void
    {
        $apiSource = str_replace("\r\n", "\n", file_get_contents(ROOTPATH . 'app/Controllers/Api/V1Controller.php'));

        self::assertStringContainsString('getUserCompanies($userId, true)', $apiSource);
        self::assertStringContainsString("private function companyId(): int", $apiSource);
        self::assertStringContainsString("getUserAccess((int) session()->get('user_id'), \$id)", $apiSource);
        self::assertStringContainsString("throw new \\RuntimeException('You do not have access to this company.', 403)", $apiSource);
        self::assertStringContainsString("private function requirePermission(string \$permission): void\n    {\n        \$this->companyId();", $apiSource);
        self::assertStringContainsString('is_root_company', $apiSource);
        self::assertStringContainsString("public function deleteCustomer(int \$id): ResponseInterface\n    {\n        return \$this->run(function () use (\$id) {\n            \$this->requirePermission('can_create');", $apiSource);
        self::assertStringContainsString("public function generateDocket(): ResponseInterface\n    {\n        return \$this->run(function () {\n            \$this->requirePermission('can_create');", $apiSource);
        self::assertStringContainsString("public function updateBooking(int \$id): ResponseInterface\n    {\n        return \$this->run(function () use (\$id) {\n            \$this->requirePermission('can_create');", $apiSource);
        self::assertStringContainsString("public function saveTracking(): ResponseInterface\n    {\n        return \$this->run(function () {\n            \$this->requirePermission('can_edit');", $apiSource);
        self::assertStringContainsString("public function deleteTracking(int \$id): ResponseInterface\n    {\n        return \$this->run(function () use (\$id) {\n            \$this->requirePermission('can_edit');", $apiSource);
    }

    public function testLogisticsControllerGuardsRootCompanyAgainstDeletion(): void
    {
        $logisticsSource = file_get_contents(ROOTPATH . 'app/Controllers/Logistics.php');

        self::assertStringContainsString('(int) $id === 1', $logisticsSource);
        self::assertStringContainsString('The root company MA Logistic cannot be deleted', $logisticsSource);
        self::assertStringContainsString('UserCompanyAccessModel', $logisticsSource);
        self::assertStringContainsString('getUserAccess($userId, $companyId)', $logisticsSource);
        self::assertStringContainsString('hasRootAdminAccess()', $logisticsSource);
    }

    public function testAdminControllerGuardsSoleRootAdministratorRevocation(): void
    {
        $adminSource = file_get_contents(ROOTPATH . 'app/Controllers/AdminController.php');

        self::assertStringContainsString('checkRootAdmin', $adminSource);
        self::assertStringContainsString('isSoleActiveRootAdmin', $adminSource);
        self::assertStringContainsString('Cannot revoke root company access for the sole active Root Administrator', $adminSource);
        self::assertStringContainsString('Cannot remove or deactivate the sole active Root Administrator', $adminSource);
        self::assertStringContainsString('Cannot deactivate the sole active Root Administrator', $adminSource);
        self::assertStringContainsString('Cannot delete the sole active Root Administrator', $adminSource);
    }

    public function testUserManagementTableHeadersMatchExactSpecification(): void
    {
        $viewSource = file_get_contents(ROOTPATH . 'app/Views/admin/users.php');

        self::assertStringContainsString('>MASTER ENTRY</th>', $viewSource);
        self::assertStringContainsString('TRACKING &amp; POD', $viewSource);
        self::assertStringContainsString('USER MANAGEMENT', $viewSource);
        self::assertStringContainsString('toggle-permission', $viewSource);
        self::assertStringContainsString('data-permission="can_create"', $viewSource);
        self::assertStringContainsString('data-permission="can_edit"', $viewSource);
        self::assertStringContainsString('data-permission="can_delete"', $viewSource);

        $adminSource = file_get_contents(ROOTPATH . 'app/Controllers/AdminController.php');
        self::assertStringContainsString("select('user_id, COUNT(*) AS company_count')", $adminSource);
        self::assertStringNotContainsString("\$row['assignments'] =", $adminSource);
        self::assertStringContainsString("if (\$saved && \$companyId === 1)", $adminSource);
    }

    public function testOperationalPermissionsSeparationInControllersAndViews(): void
    {
        $logisticsSource = str_replace("\r\n", "\n", file_get_contents(ROOTPATH . 'app/Controllers/Logistics.php'));
        $trackingSource  = str_replace("\r\n", "\n", file_get_contents(ROOTPATH . 'app/Controllers/TrackingController.php'));
        $filterSource    = str_replace("\r\n", "\n", file_get_contents(ROOTPATH . 'app/Filters/AuthFilter.php'));
        $manageView      = str_replace("\r\n", "\n", file_get_contents(ROOTPATH . 'app/Views/logistics/manage_bookings.php'));

        // Master Entry operations in Logistics: view, edit, update, delete require can_create
        self::assertStringContainsString("public function edit(\$id)\n{\n    \$perm = \$this->checkPermission('can_create');", $logisticsSource);
        self::assertStringContainsString("public function update(\$id)\n  {\n    \$perm = \$this->checkPermission('can_create');", $logisticsSource);
        self::assertStringContainsString("public function delete(\$id = null)\n  {\n    \$perm = \$this->checkPermission('can_create');", $logisticsSource);
        self::assertStringContainsString("public function view(\$id)\n  {\n    \$perm = \$this->checkPermission('can_create');", $logisticsSource);

        // Tracking operations require can_edit
        self::assertStringContainsString('checkTrackingPermission', $trackingSource);
        self::assertStringContainsString('Tracking & POD permission denied', $trackingSource);

        // Manage bookings view partitioning
        self::assertStringContainsString('CAN_MASTER_ENTRY', $manageView);
        self::assertStringContainsString('CAN_TRACKING', $manageView);
        self::assertStringContainsString('canMaster', $manageView);
        self::assertStringContainsString('canTrack', $manageView);

        // AuthFilter route blocks
        self::assertStringContainsString('isMasterEntryRoute', $filterSource);
        self::assertStringContainsString('Access Denied: You do not have permission to view or modify shipment details.', $filterSource);
        self::assertStringContainsString('Tracking & POD permission denied', $filterSource);
    }

    public function testMasterEntryEmployeesOnlySeeShipmentNavigation(): void
    {
        $layout = str_replace("\r\n", "\n", file_get_contents(ROOTPATH . 'app/Views/layout.php'));
        $dashboard = str_replace("\r\n", "\n", file_get_contents(ROOTPATH . 'app/Views/logistics/dashboard.php'));

        self::assertStringContainsString("\$isAdmin        = (\$userRole === 'admin');", $layout);
        self::assertStringContainsString("<?php if (\$isAdmin): ?>\n                <!-- Masters Collapse -->", $layout);
        self::assertStringContainsString('<?php if ($isAdmin || $canUserMgmt): ?>', $layout);
        self::assertStringContainsString("<?php if (\$isAdmin): ?>\n                            <a class=\"sidebar-nav-item", $layout);
        self::assertStringContainsString("<?php if (session()->get('role') === 'admin'): ?>\n            <a href=\"<?= base_url('masters/customers') ?>\"", $dashboard);
    }

    public function testUpdateUserRouteAndValidationSupport(): void
    {
        $routesSource = file_get_contents(ROOTPATH . 'app/Config/Routes.php');
        self::assertStringContainsString("\$routes->post('admin/updateUser', 'AdminController::updateUser');", $routesSource);

        $adminSource = file_get_contents(ROOTPATH . 'app/Controllers/AdminController.php');
        self::assertStringContainsString('public function updateUser()', $adminSource);
        self::assertStringContainsString("where('username', \$username)->where('id !=', \$userId)", $adminSource);
        self::assertStringContainsString("where('email', \$email)->where('id !=', \$userId)", $adminSource);
        self::assertStringContainsString('Cannot demote the sole active Root Administrator', $adminSource);

        $viewSource = file_get_contents(ROOTPATH . 'app/Views/admin/users.php');
        self::assertStringContainsString('id="editUserModal"', $viewSource);
        self::assertStringContainsString('id="editUsername"', $viewSource);
        self::assertStringContainsString('id="editEmail"', $viewSource);
        self::assertStringContainsString('btn-edit-user', $viewSource);

        // Database test for user update
        $this->db->transStart();
        try {
            $userModel = new UserModel($this->db);
            $origUser = 'edit_test_' . bin2hex(random_bytes(3));
            $userId = $userModel->insert([
                'username'   => $origUser,
                'email'      => $origUser . '@example.com',
                'password'   => 'password123',
                'role'       => 'user',
                'is_active'  => 1,
            ]);
            self::assertGreaterThan(0, $userId);

            $newUsername = 'updated_' . bin2hex(random_bytes(3));
            $newEmail    = $newUsername . '@example.com';

            $userModel->update($userId, [
                'username' => $newUsername,
                'email'    => $newEmail,
            ]);

            $fetched = $userModel->find($userId);
            self::assertSame($newUsername, $fetched['username']);
            self::assertSame($newEmail, $fetched['email']);
        } finally {
            $this->db->transRollback();
        }
    }
}

