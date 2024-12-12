<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pug 정보</title>
    <link rel="stylesheet" href="../static/css/header.css">
    <link rel="stylesheet" href="../static/css/styles.css">
    <link rel="stylesheet" href="../static/css/page.css">
    <link rel="stylesheet" href="../static/css/footer.css">
</head>
<body>
    <div id="header-container">
        <?php include '../templates/header.php'; ?>
    </div>

    <div class="page-container">
        <div class="breed-header">
            <h1>Pug</h1>
        </div>

        <div class="breed-image-section">
            <img src="../down_img/06.jpg" alt="Pug" class="breed-image">
        </div>

        <div class="info-section">
            <h2>역사와 유래 및 기원</h2>
            <div class="info-content">
                <p>원산지는 중국이다. 송나라 시대 황제가 키우는 군견으로 추정되며, 원래 송나라에서 키우던 퍼그는 개다운 정상적인 외모로 지금과는 상당히 다르게 생겼다. 그러다가 실크로드로 전세계를 휘어잡고 있던 중국인들이 영국으로 퍼그를 데리고 갔고 그것이 발단이 되어 퍼그가 유럽에 알려지게 된다. 16세기쯤 영국으로 건너갔다고 한다. 당시 유럽인들은 불도그 같은 못생긴 개를 키우는게 유행이었고, 퍼그를 못생긴 개로 개량시켰다. 원래는 고대 불도그처럼 입이 튀어나오고 멋있게 생긴 종이었으나 서양인들이 개량한다는 과정에서 오히려 못생겨지고 중요 기능이 퇴화된 종이 현재 퍼그다. 훼방꾼 아담에서는 퍼그 1만 마리가 단 50마리의 유전적 다양성이 있다고 한다. 오스트리아 공작만큼이나 근친교배되었다. 그 때문인지 후술할 사망원인 대부분이 안면종이다. 17세기 경에는 네덜란드 왕가에서도 길렀다. 때문에 유럽인들 중엔 퍼그의 원산지가 네덜란드인 줄 알고 있는 사람들도 있다고 한다. 나폴레옹의 아내가 키웠던 개가 퍼그라고 한다. 리처드 도킨스의 지상 최대의 쇼의 내용에 따르면 퍼그와 같은 주둥이 짧은 개들은 강아지 형태를 유지하면서 성숙(유형성숙)하도록 만든 결과라고 한다. 일설에 의하면 티베탄 스파니엘 혹은 마스티프 종류를 소형화한 것이 퍼그라고 한다.</p>
            </div>
        </div>

        <div class="section-divider"></div>

        <div class="info-section">
            <h2>성격과 특징</h2>
            <div class="info-content">
                <div class="characteristics">
                    <div class="characteristic-card">
                        <h3>성격</h3>
                        <p>느긋하고 순하지만 나름대로 고집이 있다. 주인의 명령보다는 자기 뜻대로 행동하려는 경향이 있어서 주인 입장에서는 개가 멍청하거나 자기가 무시당한다는 생각이 들기도 한다. 하지만 이 녀석 입장에서는 졸리거나 귀찮은 것일 뿐.</p>
                    </div>

                    <div class="characteristic-card">
                        <h3>특징</h3>
                        <p>대신 주인이 장난을 쳐도 화내지 않을 정도로 너그러우며 낯선 사람에게도 공격성을 드러내지는 않는다. 식탐이 상상을 초월할 정도로 강하므로 사료를 줄 때는 항상 한 번에 정해진 분량만큼만 주는 것이 좋다. 특히 중성화수술을 받은 개들은 살이 30% 더 찌기 때문에 중성화 전용 사료를 먹이든가 해야 한다.</p>
                    </div>

                    <div class="characteristic-card">
                        <h3>비만과 건강</h3>
                        <p>안 그래도 움직이기 싫어하는 개인데, 더 살찌기 쉬워지기 때문에 자칫하면 비만으로 인한 당뇨가 온다. 그리고 주둥이가 짧기에 어쩔 수 없는 거지만 잘 때 코를 고니 소음에 예민한 사람은 다시 한 번 생각해 볼 것.</p>
                    </div>

                    <div class="characteristic-card">
                        <h3>성격적 특징</h3>
                        <p>질투심이 있는 편이고, 외로운 것을 싫어하는 편이라 분리불안에도 주의해야 한다. 게으른 편이고, 이 때문에 비만이 오기 쉬우니 특히 주의해야 하는 견종.</p>
                    </div>

                    <div class="characteristic-card">
                        <h3>활동량</h3>
                        <p>다만 이 게으름과 단두종 특유의 저질 폐활량이 합쳐져 금새 지치기 때문에 운동량이 적어서 실내에서 키우기 더 부담 없는 면도 있다. 종합적으로 보면 완벽한 실내 애완견이지만 주의할 점은 있다.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="footer-container">
        <?php include '../templates/footer.php'; ?>
    </div>

        <script src="../static/js/menu.js"></script>
    <script src="../static/js/script.js"></script>
</body>
</html>
