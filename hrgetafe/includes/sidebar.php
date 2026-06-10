<?php
/**
 * SIDEBAR NAVIGATION
 * HRGotale - Human Resources Information System
 */
?>

<div class="sidebar bg-dark">
    <div class="sidebar-header">
        <h5 class="text-white">
            <i class="fas fa-bars"></i> Menu
        </h5>
    </div>
    
    <ul class="nav flex-column">
        <!-- Dashboard -->
        <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        
        <!-- Employees Module -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#employeesMenu" role="button">
                <i class="fas fa-users"></i> Employees
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="employeesMenu">
                <ul class="nav flex-column ms-3">
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/employees/list.php">
                        <i class="fas fa-list"></i> Employee List
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/employees/add.php">
                        <i class="fas fa-plus"></i> Add Employee
                    </a></li>
                </ul>
            </div>
        </li>
        
        <!-- Attendance Module -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#attendanceMenu" role="button">
                <i class="fas fa-clock"></i> Attendance
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="attendanceMenu">
                <ul class="nav flex-column ms-3">
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/attendance/log.php">
                        <i class="fas fa-sign-in-alt"></i> Log Attendance
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/attendance/records.php">
                        <i class="fas fa-file-alt"></i> Attendance Records
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/attendance/report.php">
                        <i class="fas fa-chart-bar"></i> Attendance Report
                    </a></li>
                </ul>
            </div>
        </li>
        
        <!-- Leave Module -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#leaveMenu" role="button">
                <i class="fas fa-calendar-times"></i> Leave Management
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="leaveMenu">
                <ul class="nav flex-column ms-3">
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/leave/request.php">
                        <i class="fas fa-plus"></i> Request Leave
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/leave/my_requests.php">
                        <i class="fas fa-list"></i> My Requests
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/leave/approvals.php">
                        <i class="fas fa-check-circle"></i> Approvals
                    </a></li>
                </ul>
            </div>
        </li>
        
        <!-- Payroll Module -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#payrollMenu" role="button">
                <i class="fas fa-money-bill-wave"></i> Payroll
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="payrollMenu">
                <ul class="nav flex-column ms-3">
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/payroll/manage.php">
                        <i class="fas fa-cogs"></i> Manage Payroll
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/payroll/records.php">
                        <i class="fas fa-history"></i> Payroll Records
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/payroll/payslips.php">
                        <i class="fas fa-file-pdf"></i> Payslips
                    </a></li>
                </ul>
            </div>
        </li>
        
        <!-- Reports Module -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#reportsMenu" role="button">
                <i class="fas fa-chart-line"></i> Reports
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="reportsMenu">
                <ul class="nav flex-column ms-3">
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/reports/employee_report.php">
                        <i class="fas fa-user-tie"></i> Employee Report
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/reports/attendance_report.php">
                        <i class="fas fa-chart-bar"></i> Attendance Report
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>modules/reports/payroll_report.php">
                        <i class="fas fa-chart-pie"></i> Payroll Report
                    </a></li>
                </ul>
            </div>
        </li>
        
        <!-- Admin Section (Only for Admin) -->
        <?php if ($_SESSION['role'] == ROLE_ADMIN): ?>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#adminMenu" role="button">
                <i class="fas fa-shield-alt"></i> Administration
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="adminMenu">
                <ul class="nav flex-column ms-3">
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>admin/users.php">
                        <i class="fas fa-user-shield"></i> Manage Users
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>admin/audit.php">
                        <i class="fas fa-history"></i> Audit Logs
                    </a></li>
                    <li><a class="nav-link" href="<?php echo BASE_URL; ?>admin/settings.php">
                        <i class="fas fa-sliders-h"></i> System Settings
                    </a></li>
                </ul>
            </div>
        </li>
        <?php endif; ?>
    </ul>
</div>
