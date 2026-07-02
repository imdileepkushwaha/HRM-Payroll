<?php

function portal_docs_meta(): array
{
    return [
        'product' => 'Payroll HR Management System',
        'version' => '1.0',
        'generated_label' => 'Documentation edition',
    ];
}

function get_admin_portal_documentation(): array
{
    return [
        'title' => 'Admin Portal',
        'subtitle' => 'Complete feature guide for HR administrators & payroll teams',
        'accent' => [109, 40, 217],
        'intro' => 'The Admin Portal is the control centre for your organisation. Use it to manage employees, process payroll, review attendance and leave, run reports, and configure company policies. Access requires an admin username, password, and authorised branch.',
        'workflows' => [
            [
                'title' => 'Monthly payroll cycle',
                'steps' => ['Sync attendance & leave', 'Review punch logs', 'Run payroll centre', 'Lock period', 'Send salary slips'],
            ],
            [
                'title' => 'Leave approval flow',
                'steps' => ['Employee applies leave', 'Appears in Approvals', 'Admin approves/rejects', 'Attendance auto-updated'],
            ],
            [
                'title' => 'New employee onboarding',
                'steps' => ['Add employee record', 'Assign branch & manager', 'Upload documents', 'Configure salary split'],
            ],
        ],
        'sections' => [
            [
                'title' => 'Overview',
                'modules' => [
                    [
                        'name' => 'Dashboard',
                        'summary' => 'At-a-glance view of headcount, attendance health, pending approvals, and payroll status for the active branch.',
                        'features' => [
                            'Branch-wise summary cards and quick stats',
                            'Pending approval counts with direct links',
                            'Recent activity and payroll period indicators',
                            'Shortcuts to high-traffic modules',
                        ],
                        'steps' => [
                            'Sign in at Admin Login with your branch selected.',
                            'Review dashboard widgets for the current month.',
                            'Click any stat card to jump to the related module.',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'People & HR',
                'modules' => [
                    [
                        'name' => 'Employees',
                        'summary' => 'Maintain the employee master — personal details, job information, salary structure, documents, and portal access.',
                        'features' => [
                            'Add, edit, activate/deactivate employees',
                            'Department, designation, manager & branch assignment',
                            'Salary components and bank details',
                            'Document verification and profile change requests',
                        ],
                        'steps' => [
                            'Open Employees from the sidebar.',
                            'Use Add Employee or search an existing record.',
                            'Complete personal, job, and payroll tabs before saving.',
                            'Review pending document requests from the employee view.',
                        ],
                    ],
                    [
                        'name' => 'Departments',
                        'summary' => 'Organise employees into departments for reporting, org structure, and filtering.',
                        'features' => ['Create and rename departments', 'Assign employees during onboarding', 'Used across reports and org chart'],
                        'steps' => ['Navigate to Departments.', 'Add or edit department names.', 'Map employees from the Employees screen.'],
                    ],
                    [
                        'name' => 'Org chart',
                        'summary' => 'Visual hierarchy of managers and direct reports across the organisation.',
                        'features' => ['Manager–report relationships', 'Drag-free read-only tree view', 'Helps validate team structure'],
                        'steps' => ['Set each employee\'s manager in their profile.', 'Open Org chart to verify reporting lines.'],
                    ],
                    [
                        'name' => 'Recruitment',
                        'summary' => 'Track job applicants through hiring stages from applied to hired.',
                        'features' => ['Pipeline stages: applied, screening, interview, offered, hired, rejected', 'Applicant notes and status updates', 'Export candidate list'],
                        'steps' => ['Add new applicants with role and contact info.', 'Move cards across stages as hiring progresses.', 'Mark hired to begin onboarding in Employees.'],
                    ],
                    [
                        'name' => 'Performance',
                        'summary' => 'Run performance review cycles with self-review and manager evaluation.',
                        'features' => ['Review cycles with open/closed status', 'Employee self-assessment forms', 'Manager ratings and feedback', 'Export review data'],
                        'steps' => ['Create or open a review cycle.', 'Assign employees to the cycle.', 'Monitor completion from the performance board.'],
                    ],
                    [
                        'name' => 'Exit & F&F',
                        'summary' => 'Manage resignations, notice period, full & final settlement, and exit PDF.',
                        'features' => ['Exit initiation and status tracking', 'F&F calculation breakdown', 'Settlement PDF generation', 'Clearance workflow'],
                        'steps' => ['Record exit when resignation is approved.', 'Enter settlement components.', 'Generate F&F PDF for finance sign-off.'],
                    ],
                    [
                        'name' => 'Helpdesk (admin)',
                        'summary' => 'Respond to employee support tickets raised from the employee portal.',
                        'features' => ['Ticket queue by status: open, answered, closed', 'Reply with admin notes', 'Filter by branch'],
                        'steps' => ['Open Helpdesk from People section.', 'Select a ticket and read employee description.', 'Post reply and update status.'],
                    ],
                ],
            ],
            [
                'title' => 'Finance',
                'modules' => [
                    [
                        'name' => 'Expenses',
                        'summary' => 'Review and approve employee expense claims submitted through the portal.',
                        'features' => ['Pending / approved / rejected filters', 'Receipt attachment review', 'Amount totals by employee', 'Branch-scoped listing'],
                        'steps' => ['Open Expenses.', 'Filter pending claims.', 'Approve or reject with a review note.'],
                    ],
                    [
                        'name' => 'Assets',
                        'summary' => 'Track company assets assigned to employees such as laptops, phones, and equipment.',
                        'features' => ['Asset register with categories', 'Assign to employee with date', 'Return tracking on exit'],
                        'steps' => ['Add asset with category and serial info.', 'Assign from employee profile or assets list.', 'Mark returned when employee exits.'],
                    ],
                ],
            ],
            [
                'title' => 'Calendar & communication',
                'modules' => [
                    [
                        'name' => 'Holidays',
                        'summary' => 'Define public holidays per branch that affect attendance and payroll.',
                        'features' => ['Branch-specific holiday calendar', 'Bulk upload template', 'Reflects on employee attendance calendars'],
                        'steps' => ['Add holidays manually or upload spreadsheet.', 'Verify holidays appear on team calendar.'],
                    ],
                    [
                        'name' => 'Weekoff roster',
                        'summary' => 'Configure weekly off patterns and exceptions for employees.',
                        'features' => ['Default weekoff rules', 'Employee-level overrides', 'Feeds attendance calculation'],
                        'steps' => ['Set roster pattern for branch or employee.', 'Sync attendance after changes.'],
                    ],
                    [
                        'name' => 'Team calendar',
                        'summary' => 'Admin view of who is on leave and branch holidays for a selected month.',
                        'features' => ['Month navigation', 'Leave overlay on calendar', 'Holiday markers'],
                        'steps' => ['Pick month and branch context.', 'Review leave density before approvals.'],
                    ],
                    [
                        'name' => 'Announcements',
                        'summary' => 'Publish company-wide or branch-specific news visible in the employee portal.',
                        'features' => ['Pin important posts', 'Rich text body', 'Instant visibility on employee dashboard'],
                        'steps' => ['Create announcement with title and body.', 'Optionally pin for top placement.', 'Publish — employees see it immediately.'],
                    ],
                ],
            ],
            [
                'title' => 'Attendance',
                'modules' => [
                    [
                        'name' => 'Upload attendance',
                        'summary' => 'Import or correct monthly attendance records before payroll processing.',
                        'features' => ['Spreadsheet import', 'Manual month grid', 'Clear month utility', 'Status codes: present, absent, leave, etc.'],
                        'steps' => ['Select year and month.', 'Upload file or edit grid cells.', 'Validate totals before payroll run.'],
                    ],
                    [
                        'name' => 'Punch logs',
                        'summary' => 'Raw punch-in and punch-out events from biometric, web, or manual entry.',
                        'features' => ['Filter by employee and date range', 'Late / early flags', 'Missing punch detection', 'Delete erroneous punches'],
                        'steps' => ['Search employee or date.', 'Investigate anomalies.', 'Cross-check with punch report.'],
                    ],
                    [
                        'name' => 'Punch report',
                        'summary' => 'Summarised punch analytics — work hours, late ins, early outs per employee.',
                        'features' => ['Period-based summary', 'Export-friendly table', 'Supports regularization decisions'],
                        'steps' => ['Choose payroll month.', 'Export or review outliers.', 'Follow up via employee regularization requests.'],
                    ],
                ],
            ],
            [
                'title' => 'Leave',
                'modules' => [
                    [
                        'name' => 'Approvals',
                        'summary' => 'Central inbox for leave, attendance correction, WFH, regularization, and related requests.',
                        'features' => [
                            'Unified pending queue with counts',
                            'Approve / reject with comments',
                            'Automatic attendance sync on leave approval',
                            'Expense and profile requests included',
                        ],
                        'steps' => ['Open Approvals — badge shows pending count.', 'Review request details.', 'Approve or reject; employee is notified.'],
                    ],
                    [
                        'name' => 'Leave history',
                        'summary' => 'Historical log of all leave transactions across employees.',
                        'features' => ['Filter by employee, type, status', 'Audit trail of decisions', 'Useful for dispute resolution'],
                        'steps' => ['Search employee or date range.', 'Export or review approved vs rejected.'],
                    ],
                    [
                        'name' => 'Leave balances',
                        'summary' => 'View and adjust yearly leave quotas and consumed balances.',
                        'features' => ['Per leave-type balance', 'Manual balance correction', 'Quota from settings applied annually'],
                        'steps' => ['Select employee.', 'Review balance columns.', 'Adjust if carry-forward or correction needed.'],
                    ],
                ],
            ],
            [
                'title' => 'Payroll',
                'modules' => [
                    [
                        'name' => 'Payroll center',
                        'summary' => 'Core payroll engine — calculate salaries from attendance, apply deductions, lock periods.',
                        'features' => [
                            'Monthly salary calculation per employee',
                            'Paid days from attendance integration',
                            'Adjustments and arrears',
                            'Period lock to prevent changes after processing',
                        ],
                        'steps' => [
                            'Ensure attendance is finalised for the month.',
                            'Open Payroll center and select period.',
                            'Run calculation and review totals.',
                            'Lock period when satisfied.',
                        ],
                    ],
                    [
                        'name' => 'Slip logs & distribution',
                        'summary' => 'Generate PDF salary slips and email them to employees.',
                        'features' => ['Bulk slip generation', 'Send history log', 'Resend individual slips', 'Employee download in portal'],
                        'steps' => ['After payroll lock, open slip logs.', 'Generate slips for the period.', 'Send to employees via configured SMTP.'],
                    ],
                ],
            ],
            [
                'title' => 'Reports & system',
                'modules' => [
                    [
                        'name' => 'Reports',
                        'summary' => 'Attendance and payroll analytics for management review.',
                        'features' => ['Attendance summary report', 'Payroll cost report', 'Punch analytics', 'Branch filters'],
                        'steps' => ['Choose report type.', 'Set date range and branch.', 'Review on screen or export.'],
                    ],
                    [
                        'name' => 'Roles & permissions',
                        'summary' => 'Control which admin users can access each module.',
                        'features' => ['Role-based permission matrix', 'Assign roles to admin users', 'Granular module toggles'],
                        'steps' => ['Define role in Roles screen.', 'Tick permitted modules.', 'Assign role when creating admin user.'],
                    ],
                    [
                        'name' => 'Audit log',
                        'summary' => 'Security and compliance trail of sensitive admin actions.',
                        'features' => ['Timestamped action records', 'User attribution', 'Filter by action type'],
                        'steps' => ['Open Audit log periodically.', 'Investigate unusual entries.'],
                    ],
                    [
                        'name' => 'Settings',
                        'summary' => 'Company profile, payroll rules, leave types, punch policy, SMTP, and branches.',
                        'features' => [
                            'Company name, logo initial, policies HTML',
                            'Salary split percentages and statutory settings',
                            'Leave types and yearly quotas',
                            'Office timings, grace minutes, face login toggle',
                            'SMTP for slip and notification emails',
                        ],
                        'steps' => ['Open Settings tabs one by one.', 'Save each section after changes.', 'Test SMTP from settings tools.'],
                    ],
                ],
            ],
        ],
    ];
}

function get_employee_portal_documentation(): array
{
    return [
        'title' => 'Employee Portal',
        'subtitle' => 'Self-service guide for every team member',
        'accent' => [14, 165, 233],
        'intro' => 'The Employee Portal lets staff manage day-to-day HR tasks without contacting admin. Sign in with Employee ID and password (or face login if enabled). Managers get extra team features.',
        'workflows' => [
            [
                'title' => 'Apply for leave',
                'steps' => ['Open Apply leave', 'Pick dates & type', 'Submit for approval', 'Track on calendar'],
            ],
            [
                'title' => 'Download salary slip',
                'steps' => ['Admin processes payroll', 'Slip published', 'Open Salary slips', 'Download PDF'],
            ],
            [
                'title' => 'Punch regularization',
                'steps' => ['Missed punch detected', 'Submit regularization', 'Manager/admin approves', 'Attendance updated'],
            ],
        ],
        'sections' => [
            [
                'title' => 'Home',
                'modules' => [
                    [
                        'name' => 'Dashboard',
                        'summary' => 'Personalised home with greeting, attendance snapshot, punch card, and announcements.',
                        'features' => [
                            'Live punch in / punch out card',
                            'Paid days and present count for current month',
                            'Pending request quota remaining',
                            'Pinned company announcements',
                            'Quick links to attendance and slips',
                        ],
                        'steps' => ['Sign in at Employee login.', 'Review stats and punch status.', 'Use shortcuts for frequent tasks.'],
                    ],
                ],
            ],
            [
                'title' => 'Attendance',
                'modules' => [
                    [
                        'name' => 'My attendance',
                        'summary' => 'Monthly calendar showing present, absent, leave, holiday, and weekoff codes.',
                        'features' => ['Colour-coded calendar', 'Submit attendance correction requests', 'Period navigation', 'Link to punch history'],
                        'steps' => ['Select month from period nav.', 'Click a date to request correction if needed.', 'Wait for admin approval.'],
                    ],
                    [
                        'name' => 'Punch history',
                        'summary' => 'Day-by-day punch in/out times, work hours, and late/early flags.',
                        'features' => ['Daily timeline view', 'Office timing reference', 'Grace period display', 'Monthly stats summary'],
                        'steps' => ['Open Punch history.', 'Review days with missing or late punches.', 'Raise regularization if required.'],
                    ],
                    [
                        'name' => 'Punch regularization',
                        'summary' => 'Request correction when a punch was missed or device failed.',
                        'features' => ['Date-specific requests', 'Reason note field', 'Status tracking', 'History timeline'],
                        'steps' => ['Choose date and intended in/out times.', 'Explain reason.', 'Submit — track in history panel.'],
                    ],
                    [
                        'name' => 'Work from home',
                        'summary' => 'Request WFH for specific dates ahead of time.',
                        'features' => ['Future date selection', 'Manager approval flow', 'Monthly request list', 'Status badges'],
                        'steps' => ['Use sidebar form on WFH page.', 'Pick date and add work plan.', 'Await approval notification.'],
                    ],
                ],
            ],
            [
                'title' => 'Requests',
                'modules' => [
                    [
                        'name' => 'Apply leave',
                        'summary' => 'Submit leave applications against available balances.',
                        'features' => [
                            'Leave type picker with balance display',
                            'From / to date range',
                            'Monthly request limit enforcement',
                            'Cancel pending requests',
                            'Calendar preview of approved leave',
                        ],
                        'steps' => ['Check balances in stats cards.', 'Fill leave form in side panel.', 'Submit before monthly quota exhausted.'],
                    ],
                    [
                        'name' => 'Expense claims',
                        'summary' => 'Upload receipts and claim reimbursements.',
                        'features' => ['Category and amount entry', 'Receipt file upload', 'Pending / approved amounts', 'Claim history cards'],
                        'steps' => ['Click new claim.', 'Attach receipt PDF or image.', 'Track approval in list.'],
                    ],
                    [
                        'name' => 'Performance review',
                        'summary' => 'Complete self-review during open performance cycles.',
                        'features' => ['Cycle picker', 'Self-assessment form', 'View manager feedback when published', 'Open vs completed status'],
                        'steps' => ['Select active review.', 'Fill self-review fields.', 'Save before cycle closes.'],
                    ],
                    [
                        'name' => 'Resignation & exit',
                        'summary' => 'Initiate resignation and track exit progress.',
                        'features' => ['Resignation form with last working day', 'Exit stepper UI', 'F&F status visibility', 'Single active exit guard'],
                        'steps' => ['Submit resignation with note.', 'Follow exit checklist.', 'Contact HR for clearance queries.'],
                    ],
                ],
            ],
            [
                'title' => 'My team (managers)',
                'modules' => [
                    [
                        'name' => 'My team',
                        'summary' => 'View direct reports, manager chain, and team quick stats.',
                        'features' => ['Direct reports list', 'Manager contact card', 'Pending approval shortcut', 'Team size badge'],
                        'steps' => ['Visible only if you have reports.', 'Review roster and contact info.'],
                    ],
                    [
                        'name' => 'Team approvals',
                        'summary' => 'Managers can action pending leave, WFH, and regularization for their team.',
                        'features' => ['Grouped by request type', 'Approve / reject buttons', 'Pending count in hero', 'Audit trail per request'],
                        'steps' => ['Open Team approvals.', 'Review each card.', 'Approve or reject with comment.'],
                    ],
                    [
                        'name' => 'Team calendar',
                        'summary' => 'See who is on approved leave and branch holidays.',
                        'features' => ['Month grid with leave tags', 'Holiday list', 'On-leave today panel', 'Branch context'],
                        'steps' => ['Navigate months.', 'Plan coverage using leave overlay.'],
                    ],
                ],
            ],
            [
                'title' => 'Company',
                'modules' => [
                    [
                        'name' => 'Announcements',
                        'summary' => 'Read company news and pinned updates from HR.',
                        'features' => ['Pinned posts highlighted', 'Newest first', 'Full text display'],
                        'steps' => ['Open Announcements from sidebar.', 'Read pinned items first.'],
                    ],
                    [
                        'name' => 'Notifications',
                        'summary' => 'Action items requiring your attention.',
                        'features' => ['Pending leave, profile, expense alerts', 'Priority indicators', 'Deep links to relevant pages'],
                        'steps' => ['Check badge count on sidebar.', 'Tap each item to resolve.'],
                    ],
                    [
                        'name' => 'Policies',
                        'summary' => 'Employee handbook published by HR.',
                        'features' => ['HTML policy content from settings', 'Always available reference'],
                        'steps' => ['Open Policies.', 'Scroll handbook sections.'],
                    ],
                    [
                        'name' => 'HR helpdesk',
                        'summary' => 'Raise tickets for payroll, IT, leave, or HR queries.',
                        'features' => ['Category selection', 'Ticket timeline', 'HR reply thread', 'Status tracking'],
                        'steps' => ['Submit ticket with subject and body.', 'Watch for HR reply in history.'],
                    ],
                    [
                        'name' => 'HR letters',
                        'summary' => 'Request experience, relieving, NOC, or Form 16 letters.',
                        'features' => ['Letter type dropdown', 'Note for HR', 'Request history', 'Links to documents module'],
                        'steps' => ['Pick letter type.', 'Add context note.', 'Track status in history.'],
                    ],
                ],
            ],
            [
                'title' => 'Payroll & account',
                'modules' => [
                    [
                        'name' => 'Salary slips',
                        'summary' => 'Download PDF payslips after admin publishes them.',
                        'features' => ['Month-wise slip list', 'Download only when released', 'Period stats', 'Secure PDF view'],
                        'steps' => ['Open Salary slips.', 'Select month with available slip.', 'Download or view PDF.'],
                    ],
                    [
                        'name' => 'YTD summary',
                        'summary' => 'Year-to-date earnings and attendance overview.',
                        'features' => ['Annual net earnings', 'Month-by-month breakdown', 'Paid days per month', 'Link to slips'],
                        'steps' => ['Choose year from nav.', 'Review highlight cards and monthly table.'],
                    ],
                    [
                        'name' => 'My assets',
                        'summary' => 'Company equipment assigned to you.',
                        'features' => ['Asset category icons', 'Assigned date', 'Serial / tag info'],
                        'steps' => ['Review assigned items.', 'Report issues via helpdesk.'],
                    ],
                    [
                        'name' => 'My documents',
                        'summary' => 'Upload identity and education proofs; download approved files.',
                        'features' => [
                            'Document type slots (ID, marksheets, etc.)',
                            'Upload with admin verification',
                            'Progress ring for profile completion',
                            'Office letter downloads when approved',
                        ],
                        'steps' => ['Open Documents.', 'Upload missing types.', 'Wait for admin approval badge.'],
                    ],
                    [
                        'name' => 'My details',
                        'summary' => 'View and request updates to personal profile information.',
                        'features' => ['Profile hero with avatar', 'Request change form', 'Pending approval lock', 'Password change link'],
                        'steps' => ['Review current details.', 'Submit change request if needed.', 'Cannot edit while pending approval.'],
                    ],
                    [
                        'name' => 'Face login',
                        'summary' => 'Optional biometric sign-in when enabled by company.',
                        'features' => ['Enrol face samples', 'Password fallback always available', 'Remove enrolment option'],
                        'steps' => ['Open Face login when enabled.', 'Capture samples in good lighting.', 'Use Face tab on login screen.'],
                    ],
                ],
            ],
        ],
    ];
}
