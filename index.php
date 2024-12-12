<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="AI를 활용한 강아지 품종 식별 서비스">
    <meta name="keywords" content="강아지, 품종, AI, 견종 식별">
    <link rel="stylesheet" href="static/css/header.css">
    <link rel="stylesheet" href="static/css/styles.css">
    <link rel="stylesheet" href="static/css/footer.css">
    <title>AI 강아지 품종 식별 | Dog Breed Identifier</title>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="container">
        <main>
            <section id="upload-section" class="fade-in">
                <h1 class="main-title">AI 강아지 품종 식별</h1>
                <p class="subtitle">사진을 업로드하면 AI가 강아지의 품종을 분석해드립니다</p>

                <form id="upload-form" enctype="multipart/form-data" class="upload-form">
                    <div class="upload-area" id="upload-area">
                        <input type="file" 
                               id="image-upload"
                               accept="image/*"
                               name="image"
                               class="file-input"
                               hidden />
                        <div class="upload-content">
                            <div class="upload-icon">📷</div>
                            <p class="upload-text">여기를 클릭하거나 사진을 드래그하세요</p>
                            <p class="upload-hint">지원 형식: JPG, PNG, GIF (최대 5MB)</p>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" 
                                class="upload-button primary-button"
                                onclick="document.getElementById('image-upload').click();">
                            <span class="button-icon">📷</span>
                            사진 선택
                        </button>
                        <button type="submit" class="upload-button analyze-button">
                            <span class="button-icon">🔍</span>
                            견종 분석하기
                        </button>
                    </div>
                </form>

                <div id="result" class="result"></div>
            </section>

            <section id="info-section" class="info-section fade-in">
                <h2 class="section-title">서비스 안내</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-icon">🎯</div>
                        <h3>정확한 분석</h3>
                        <p>고성능 AI 모델을 통한<br>정확한 품종 식별</p>
                    </div>
                    <div class="info-card">
                        <div class="info-icon">⚡</div>
                        <h3>빠른 결과</h3>
                        <p>즉각적인 분석 결과<br>실시간 피드백</p>
                    </div>
                    <div class="info-card">
                        <div class="info-icon">🔒</div>
                        <h3>안전한 서비스</h3>
                        <p>개인정보 보호<br>안전한 파일 처리</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <script src="../static/js/menu.js"></script>
    <script src="../static/js/script.js"></script>
</body>
</html>