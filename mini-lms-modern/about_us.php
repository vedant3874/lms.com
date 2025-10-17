<?php
include 'header.php';
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Learning Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .team-card {
            transition: all 0.3s ease;
        }
        .team-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .feature-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">About Our Learning Platform</h1>
            <p class="text-xl max-w-3xl mx-auto mb-8">Empowering learners and educators through innovative technology and accessible education for everyone.</p>
            <div class="flex justify-center space-x-4">
                <a href="browse_courses.php" class="bg-white text-purple-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">Browse Courses</a>
                <a href="contact.php" class="bg-transparent border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-700 transition duration-300">Contact Us</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="stat-card bg-white p-6 rounded-xl shadow-lg text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">
                        <?php
                        $course_count = $conn->query("SELECT COUNT(*) as count FROM courses");
                        $count = $course_count->fetch_assoc();
                        echo $count['count'] ? $count['count'] : '50+';
                        ?>
                    </div>
                    <div class="text-gray-600">Courses Available</div>
                </div>
                <div class="stat-card bg-white p-6 rounded-xl shadow-lg text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">
                        <?php
                        $student_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student'");
                        $count = $student_count->fetch_assoc();
                        echo $count['count'] ? $count['count'] : '1000+';
                        ?>
                    </div>
                    <div class="text-gray-600">Active Students</div>
                </div>
                <div class="stat-card bg-white p-6 rounded-xl shadow-lg text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">
                        <?php
                        $teacher_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'");
                        $count = $teacher_count->fetch_assoc();
                        echo $count['count'] ? $count['count'] : '50+';
                        ?>
                    </div>
                    <div class="text-gray-600">Expert Instructors</div>
                </div>
                <div class="stat-card bg-white p-6 rounded-xl shadow-lg text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">99%</div>
                    <div class="text-gray-600">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 mb-8 md:mb-0 md:pr-12">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Our Story</h2>
                    <p class="text-gray-600 mb-4">Founded in 2020, our Learning Management System was born from a simple idea: education should be accessible to everyone, everywhere. We noticed the gaps in traditional education systems and set out to create a platform that bridges those gaps.</p>
                    <p class="text-gray-600 mb-4">What started as a small project with a handful of courses has grown into a comprehensive learning ecosystem serving thousands of students worldwide. Our mission remains the same: to provide quality education that transforms lives.</p>
                    <p class="text-gray-600">We believe in the power of learning to open doors, create opportunities, and build better futures. That's why we're committed to continuously improving our platform and expanding our course offerings.</p>
                </div>
                <div class="md:w-1/2">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80" 
                         alt="Our Team" class="rounded-lg shadow-lg w-full h-64 object-cover">
                </div>
            </div>
        </div>
    </section>

    <!-- Our Mission & Vision -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Our Mission & Vision</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">We're dedicated to creating a world where anyone, anywhere can transform their life through access to quality education.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-purple-50 p-8 rounded-lg">
                    <div class="feature-icon">
                        <i class="fas fa-bullseye text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">Our Mission</h3>
                    <p class="text-gray-600 text-center">To democratize education by providing affordable, high-quality learning opportunities to students across the globe. We strive to remove barriers to education and empower individuals to achieve their personal and professional goals.</p>
                </div>
                <div class="bg-purple-50 p-8 rounded-lg">
                    <div class="feature-icon">
                        <i class="fas fa-eye text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">Our Vision</h3>
                    <p class="text-gray-600 text-center">To become the world's leading learning platform that transforms how people learn, teach, and connect. We envision a future where education is personalized, engaging, and accessible to every aspiring learner.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Our Values</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">These core principles guide everything we do at our learning platform.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <i class="fas fa-graduation-cap text-purple-600 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Excellence in Education</h3>
                    <p class="text-gray-600">We're committed to providing the highest quality courses taught by industry experts and experienced educators.</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <i class="fas fa-users text-purple-600 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Community Focus</h3>
                    <p class="text-gray-600">We believe learning is better together. Our platform fosters collaboration and connection among students and instructors.</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <i class="fas fa-rocket text-purple-600 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Innovation</h3>
                    <p class="text-gray-600">We continuously evolve our platform with the latest technologies to enhance the learning experience for everyone.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Meet Our Team</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">The passionate individuals behind our learning platform</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="team-card bg-white p-6 rounded-lg shadow-md text-center">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=687&q=80" 
                         alt="Team Member" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
                    <h3 class="text-xl font-semibold text-gray-800 mb-1">Alex Johnson</h3>
                    <p class="text-purple-600 mb-3">Founder & CEO</p>
                    <p class="text-gray-600">Alex founded the platform with a vision to make quality education accessible to all.</p>
                </div>
                <div class="team-card bg-white p-6 rounded-lg shadow-md text-center">
                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=687&q=80" 
                         alt="Team Member" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
                    <h3 class="text-xl font-semibold text-gray-800 mb-1">Sarah Williams</h3>
                    <p class="text-purple-600 mb-3">Head of Education</p>
                    <p class="text-gray-600">Sarah ensures our curriculum meets the highest standards of educational excellence.</p>
                </div>
                <div class="team-card bg-white p-6 rounded-lg shadow-md text-center">
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" 
                         alt="Team Member" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
                    <h3 class="text-xl font-semibold text-gray-800 mb-1">Michael Chen</h3>
                    <p class="text-purple-600 mb-3">Technology Director</p>
                    <p class="text-gray-600">Michael leads our tech team in creating innovative learning solutions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 gradient-bg text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-6">Ready to Start Your Learning Journey?</h2>
            <p class="text-xl max-w-2xl mx-auto mb-8">Join thousands of students who are already transforming their lives through our courses.</p>
            <a href="register.php" class="bg-white text-purple-700 px-8 py-3 rounded-lg font-semibold text-lg hover:bg-gray-100 transition duration-300">Get Started Today</a>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>