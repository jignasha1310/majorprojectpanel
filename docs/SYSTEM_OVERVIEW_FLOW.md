# Online Examination System - Complete Process Flow

## 1. Global System Flow
User visits website  
-> Choose role journey (Student / Teacher / Admin)  
-> Register or Login  
-> Server-side validation + secure auth (`password_hash`, `password_verify`)  
-> Session creation + session ID regeneration  
-> Session inactivity timeout enforcement  
-> RBAC check (admin/teacher/student gate)  
-> Redirect to role-based dashboard  
-> Perform role-based operations  
-> Exam lifecycle (create/publish/attempt/submit/evaluate/result)  
-> Reports, analytics, and management actions

## 2. Student Process Flow

### 2.1 Student Registration
Student opens registration form  
-> Fill required details and upload ID proof  
-> Backend validates all fields and file type/size  
-> Record stored in database  
-> Student account available for login

### 2.2 Student Login
Student enters email + password  
-> System fetches student by email  
-> Verify using `password_verify()`  
-> On success: regenerate session + set `student_id` and `student_name`  
-> Redirect to Student Dashboard  
-> On failure: show secure error message

### 2.3 Student Dashboard & Profile
Student opens dashboard  
-> View stats, scheduled exams, active exams  
-> Open My Profile  
-> Edit first name, last name, phone  
-> Email is readonly  
-> Upload profile image (JPG/JPEG/PNG, size validation, MIME validation)  
-> Store file path + update profile timestamp  
-> Show success/error flash message

### 2.4 Student Exam Attempt
Student opens Take Exam  
-> Active exam list shown  
-> Prevent re-attempt if already submitted  
-> Start exam  
-> Timer starts in browser  
-> Questions shown (randomized order)  
-> Student selects options  
-> Manual submit or auto-submit on timer end  
-> Store result and answer-level details  
-> Show results and review answers

## 3. Teacher Process Flow

### 3.1 Teacher Registration (3-Step + Approval Based)
Teacher opens registration  
-> Step 1: account + personal details  
-> Validate email uniqueness, phone, password policy  
-> Hash password with bcrypt  
-> Generate registration ID (`TCH + year + sequence`)  
-> Insert teacher with `status = pending` and step tracking  
-> Step 2: academic + experience + **multi-subject selection**  
-> Save selected subjects (comma-separated)  
-> Step 3: upload documents (photo, signature, certificate, ID proof)  
-> Validate file MIME and size per document rules  
-> Final submit with `status = pending`  
-> Admin notification record created

### 3.2 Teacher Login (Status-Aware)
Teacher enters email + password  
-> Verify credentials  
-> Check application status  
-> If `approved`: login allowed  
-> If `pending`: show “Application under review.”  
-> If `rejected`: show “Application rejected. Contact admin.”  
-> Regenerate session on successful login

### 3.3 Teacher Dashboard & Profile
Approved teacher lands on dashboard  
-> View summary cards, recent students, notifications  
-> Manage exams and questions  
-> Open dedicated **My Profile** page  
-> View application status, registration details, and document preview links  
-> View rejection reason if rejected

## 4. Admin Process Flow

### 4.1 Admin Login
Admin enters credentials  
-> CSRF + password verification  
-> Session creation  
-> Redirect to Admin Dashboard

### 4.2 Teacher Request Review
Admin opens `Teacher Requests`  
-> View pending teacher applications  
-> Review personal/academic data + uploaded docs  
-> Approve or Reject (reason required on reject)  
-> Update status in DB  
-> Insert teacher notification record  
-> Send email on approve/reject (with fallback message if mail not configured)

### 4.3 Other Admin Operations
Manage students  
-> Add/update records and monitor data  
Manage teachers/exams/results  
-> View summaries, status, and system data

## 5. Security Flow
All sensitive forms include CSRF token  
-> CSRF token verified on submit  
-> Input validation + sanitization  
-> Prepared statements (PDO/MySQLi) for DB writes/reads  
-> Passwords hashed with bcrypt  
-> Session cookies hardened (`httponly`, `samesite`, strict mode)  
-> Periodic session ID regeneration  
-> Inactivity timeout auto-logout with flash message  
-> Role checks prevent direct URL access to restricted pages  
-> File uploads validated by MIME + size + safe path handling

## 6. Notification & Email Flow
System events (teacher registration, approve, reject)  
-> Insert row into `teacher_notifications`  
-> For approve/reject actions, send email via PHP `mail()`  
-> If email transport unavailable, keep DB notification and show admin warning

## 7. Data/Storage Flow
Core entities:
- `students`
- `teachers` (extended registration/status fields)
- `exams`
- `questions`
- `student_exams`
- `student_answers`
- `teacher_notifications`

Document storage:
- Teacher docs under `uploads/teacher/...`
- Student profile images under `uploads/profile/...`

## 8. UI/Responsive Flow
Design style:
- Bootstrap 5 + custom panel themes
- Card-based sections
- Status badges and alert feedback
- Multi-step progress for teacher registration

Responsive behavior:
- Desktop (`>=1024px`): full sidebar + multi-column layout
- Tablet (`768px-1023px`): compact grids
- Mobile (`<768px`): stacked forms, readable cards, accessible controls

## 9. End-to-End Teacher Approval Journey
Teacher registers (3 steps)  
-> Status saved as `pending`  
-> Admin reviews request  
-> Admin approves/rejects  
-> Notification saved  
-> Email sent  
-> Teacher login behavior controlled strictly by status  
-> Approved teacher accesses dashboard and profile

## 10. Architecture Diagram (ASCII)
```text
                         +----------------------+
                         |      End Users       |
                         |----------------------|
                         |  Student / Teacher   |
                         |  / Admin             |
                         +----------+-----------+
                                    |
                                    v
                    +-------------------------------+
                    | Frontend (HTML/CSS/Bootstrap) |
                    |-------------------------------|
                    | Login, Registration, Panels,  |
                    | Forms, Exam UI, Alerts        |
                    +---------------+---------------+
                                    |
                                    v
                +-----------------------------------------+
                | Backend (Core PHP Modules)              |
                |-----------------------------------------|
                | Auth + RBAC + Session Timeout           |
                | Student Module                          |
                | Teacher 3-Step Registration Module      |
                | Admin Approval Module                   |
                | Exam Engine (attempt/submit/evaluate)   |
                | Notification + Email Module             |
                +----------+------------------+-----------+
                           |                  |
                           | SQL (PDO/MySQLi) | File I/O
                           v                  v
              +---------------------+   +----------------------+
              | MySQL Database      |   | Upload Storage       |
              |---------------------|   |----------------------|
              | students            |   | uploads/profile      |
              | teachers            |   | uploads/teacher/*    |
              | exams               |   +----------------------+
              | questions           |
              | student_exams       |
              | student_answers     |
              | teacher_notifications|
              +----------+----------+
                         |
                         v
               +----------------------+
               | Notification/Event   |
               | Flow                 |
               |----------------------|
               | DB notification rows |
               | + Email (approve/    |
               | reject status)       |
               +----------------------+
```

## 11. How to Work This Project (Run Flow)

### 11.1 Local Setup Steps
1. Install and start XAMPP (`Apache` + `MySQL`).
2. Keep project in web root:  
   `C:\xampp\htdocs\new\majorprojectpanel`
3. Open phpMyAdmin and ensure database exists (`exampro_db`).
4. Import `database.sql` (optional if auto-bootstrap creates tables).
5. Verify write permissions for:
   - `uploads/profile/`
   - `uploads/teacher/photos/`
   - `uploads/teacher/signatures/`
   - `uploads/teacher/certificates/`
   - `uploads/teacher/idproof/`
6. Open app in browser:  
   `http://localhost/new/majorprojectpanel/`

### 11.2 Runtime Execution Diagram
```text
Start XAMPP (Apache + MySQL)
        |
        v
Open URL: /new/majorprojectpanel/
        |
        v
DB bootstrap + session security init
        |
        v
Choose role path
  |            |             |
  v            v             v
Student      Teacher       Admin
  |            |             |
  v            v             v
Login/Register Login/Register Login
  |            |             |
  v            v             v
Role Dashboard + Protected Routes
        |
        v
Perform module actions (exam/profile/approval)
        |
        v
Persist data to DB + uploads + notifications/email
```

### 11.3 Functional Workflows (How to Use)

#### A) Teacher Registration + Approval
1. Open: `pages/teacher/register.php`
2. Complete Step 1 -> Step 2 -> Step 3.
3. Login as admin.
4. Open: `pages/admin/teacher-requests.php`
5. Approve or reject application.
6. Teacher logs in from: `pages/teacher/login.php`
   - `approved` => access granted
   - `pending/rejected` => blocked with status message

#### B) Student Exam Workflow
1. Login as student: `pages/login.php`
2. Open scheduled/active exam.
3. Start exam from `pages/take-exam.php`.
4. Submit manually or by timer end.
5. View results in `pages/my-results.php` and review answers.

#### C) Teacher Management Workflow
1. Login as teacher.
2. Use dashboard/exams/questions modules.
3. Check profile and registration status at `pages/teacher/profile.php`.

### 11.4 Quick Test Checklist
- Student login/logout works.
- Teacher pending account cannot login.
- Admin approve makes teacher login active.
- Rejected teacher sees rejection message.
- File uploads store in correct folders.
- Session timeout logs user out after inactivity.
- Mobile/tablet/desktop layouts render correctly.
