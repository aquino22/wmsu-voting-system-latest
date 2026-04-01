<div align="center">

# 🗳️ VOTING SYSTEM / CORE
**WMSU Academic Election Management Engine**

[![PHP](https://img.shields.io/badge/PHP-8.x-777bb4?style=for-the-badge&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-black?style=for-the-badge)](https://opensource.org/licenses/MIT)

---

### ⚡ THE STACK
**Frontend:** `HTML5` • `CSS3` • `JavaScript` • `jQuery` • `Bootstrap`  
**Backend:** `PHP 8.x` • `PHP PDO` • `AJAX`  
**Analytics:** `Chart.js`  
**Deployment:** `MySQL` • `cPanel`

</div>

## 🛸 SYSTEM CAPABILITIES

<table width="100%">
  <tr>
    <td width="50%" valign="top">
      <h4>🔐 SECURE VOTING</h4>
      <ul>
        <li><b>Multi-Tier Scopes:</b> Central (Campus-wide) and Local (College/Department) positions.</li>
        <li><b>QR Verification:</b> Secure precinct entry via unique identity masking.</li>
        <li><b>Anti-Fraud:</b> Strict "One-Person, One-Vote" enforcement with session tracking.</li>
      </ul>
    </td>
    <td width="50%" valign="top">
      <h4>🔄 REVOTE & ARCHIVE</h4>
      <ul>
        <li><b>Tie-Detection:</b> Auto-mapping of <code>tied_candidates</code> for targeted run-offs.</li>
        <li><b>Historical Data:</b> Dedicated archiving engine for previous election cycles.</li>
        <li><b>Persistence:</b> Voters can view published winners from past elections.</li>
      </ul>
    </td>
  </tr>
</table>

---

## 👥 ROLE-BASED ACCESS CONTROL (RBAC)

| Role | Responsibility |
| :--- | :--- |
| **Admin** | Full system CRUD, configuration of election scopes, and global operations. |
| **Adviser** | Voter validation (Accept/Reject) and automated QR code distribution. |
| **Moderator** | Precinct management, overseeing voting flow, and QR-based entry clearance. |
| **Voter** | Secure candidate selection, real-time result viewing (if published), and profile management. |

---

## 📊 ANALYTICS & INSIGHTS
> **Real-time Data Visualization:** Leveraging **Chart.js** to provide high-fidelity Bar and Pie charts for instant winner identification and partylist distribution.

---

## 🛠️ CORE FEATURES

* **Dynamic Election Scopes:** Central elections for university-wide seats + Local positions editable by specific Colleges/Departments.
* **Precinct Security:** Integrated QR code system ensures only verified voters gain access to the digital ballot.
* **Result Transparency:** Dedicated interface for voters to view ongoing published results and historical winners.
* **Asynchronous UX:** Powered by **AJAX** and **jQuery** for seamless voting without page reloads.

---

## 🚀 QUICK START

### 1. Clone the Source
```bash
git clone [https://github.com/irene23/voting-system.git](https://github.com/irene23/voting-system.git)
cd voting-system
2. Database Setup (phpMyAdmin / cPanel)
Login to your cPanel or phpMyAdmin.

Create two separate databases:

wmsu_voting_system

wmsu_voting_system_archived

Locate the .sql files in the /db folder and import them into their respective databases.

3. Run the System
Configure your database credentials in the project connection file (e.g., config.php or conn.php).

Ensure your server is running PHP 8.x with the PDO extension enabled.

Access the system via your local host (e.g., localhost/voting-system) or your domain.
```

Built for the WMSU Academic Community
</div>
