<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniLMS - Modern Footer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
            color: #333;
        }
        
        main {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .content {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }
        
        p {
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        /* Footer Styles */
        .site-footer {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 3rem 0 1rem;
            margin-top: auto;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background: #3498db;
        }
        
        .footer-section p {
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        
        .footer-links a {
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .footer-links a:hover {
            color: #3498db;
            padding-left: 5px;
        }
        
        .footer-links i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background: #3498db;
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-bottom p {
            margin-bottom: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .newsletter-form {
            display: flex;
            margin-top: 1rem;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 4px 0 0 4px;
            outline: none;
        }
        
        .newsletter-form button {
            background: #3498db;
            color: white;
            border: none;
            padding: 0 1.2rem;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .newsletter-form button:hover {
            background: #2980b9;
        }
        
        .contact-info {
            list-style: none;
        }
        
        .contact-info li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .contact-info i {
            margin-right: 10px;
            margin-top: 5px;
            color: #3498db;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .footer-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .newsletter-form input,
            .newsletter-form button {
                width: 100%;
                border-radius: 4px;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>About MiniLMS</h3>
                <p>MiniLMS is a modern learning management system designed to provide the best online education experience for students and educators alike.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Courses</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Pricing</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Blog</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Support</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Help Center</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Terms of Service</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Contact Support</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Newsletter</h3>
                <p>Subscribe to our newsletter to receive updates on new courses and features.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Your email address" required>
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
                
                <h3 style="margin-top: 1.5rem;">Contact Info</h3>
                <ul class="contact-info">
                    <li><i class="fas fa-map-marker-alt"></i> <span>123 Education Street, Learning City, LC 56789</span></li>
                    <li><i class="fas fa-phone"></i> <span>+1 (555) 123-4567</span></li>
                    <li><i class="fas fa-envelope"></i> <span>info@minilms.com</span></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© 2023 MiniLMS — Built with ❤ for better education | All rights reserved</p>
        </div>
    </footer>
</body>
</html>