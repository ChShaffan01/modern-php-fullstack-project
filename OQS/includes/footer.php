    </main>
    
    <!-- Footer -->
    <footer class="footer mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>QuizMaster</h5>
                    <p>Test your knowledge with our interactive quiz platform. Learn, compete, and track your progress.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light">Home</a></li>
                        <?php if ($isLoggedIn): ?>
                            <li><a href="dashboard.php" class="text-light">Dashboard</a></li>
                            <li><a href="quiz.php" class="text-light">Quizzes</a></li>
                            <li><a href="results.php" class="text-light">My Results</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <p>Email: chshaffan@gmail.com<br>Phone: (+92) 308-6367-941</p>
                    <div class="social-icons">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> QuizMaster. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>