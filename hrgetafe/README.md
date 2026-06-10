# HRGetafe - Human Resources Information System
**Capstone Project | Getafe Local Government Unit**

---

## 📋 PROJECT OVERVIEW
A comprehensive HR Information System designed to manage employee records, attendance, leave requests, payroll, and reporting for the Getafe LGU.

**Status**: Development Phase  
**Version**: 1.0.0  
**Last Updated**: June 2026

---

## 🎯 KEY FEATURES
- ✅ Employee Records Management
- ✅ Attendance Tracking System
- ✅ Leave Request Management
- ✅ Payroll Processing
- ✅ Report Generation
- ✅ User Authentication & Authorization
- ✅ Role-Based Access Control
- ✅ Dashboard Analytics

---

## 💻 TECHNOLOGY STACK
| Component | Technology |
|-----------|-----------|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ |
| Server | Apache (XAMPP) |
| Editor | VS Code / SPCK Editor |
| Browser | Google Chrome, Firefox, Edge |

---

## 📁 PROJECT STRUCTURE


HRGetafe/ ├── config/ │ ├── database.php │ └── constants.php ├── css/ │ ├── style.css │ ├── dashboard.css │ └── responsive.css ├── js/ │ ├── main.js │ ├── validation.js │ └── ajax.js ├── includes/ │ ├── header.php │ ├── sidebar.php │ ├── footer.php │ └── auth.php ├── modules/ │ ├── employees/ │ ├── attendance/ │ ├── leave/ │ ├── payroll/ │ └── reports/ ├── uploads/ │ └── (profile images, documents) ├── database/ │ ├── schema.sql │ └── sample_data.sql ├── index.php └── .htaccess


---

## 🚀 QUICK START GUIDE

### Prerequisites
- XAMPP installed (with PHP 7.4+, MySQL 5.7+)
- Apache & MySQL services running
- VS Code or any text editor
- Basic knowledge of PHP, MySQL, HTML, CSS, JavaScript

### Installation Steps
1. Create database from `database/schema.sql`
2. Configure `config/database.php` with your credentials
3. Add all project files to `htdocs/HRGetafe`
4. Access via: `http://localhost/HRGetafe`

---

## 👥 USER ROLES
- **Admin**: Full system access, user management
- **HR Manager**: Employee & payroll management
- **HR Staff**: Data entry & basic operations
- **Supervisor**: View employee info & attendance (read-only)

---

## 📞 SUPPORT & DOCUMENTATION
For detailed documentation, see individual module guides in each folder.

---

## ⚠️ IMPORTANT NOTES
- Always backup your database regularly
- Use strong passwords for all user accounts
- Keep PHP and MySQL updated
- Test all features in a development environment first

---

**Created for**: Getafe Local Government Unit  
**Date**: June 2026  
**Researchers**: [Project Team Names]
