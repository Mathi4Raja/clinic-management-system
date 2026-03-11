// Patient Journey Flow test using browser-subagent instructions rather than raw code.

// This file documents the expected path for a manual or sub-agent verification

/*
    1. Navigate to: http://localhost/clinic%20management%20system/index.php
    2. Click "#patient-signup" tab in the Auth Modal
    3. Fill in:
        Full Name: Jane Doe Test
        Email: jane.test.e2e@clinic.com
        Password: password
        DOB: 1990-01-01
        Phone: 555-0101
    4. Submit Form and accept "Registered" alert.
    5. (If needed, switch to login tab and login with the above).
    6. Verify Patient Dashboard loads:
        - "My Appointments" is visible
        - "Leave a Review" is visible
    7. Under "Quick Booking":
        - Select any Doctor from the dropdown
        - Date: Tomorrow's date
        - Time: 10:00 AM
    8. Click "Schedule Appointment" and accept alert
    9. Refresh page and verify appointment shows in "My Appointments" queue
    10. Click "Logout" in header nav and verify return to landing page
*/
