// Утилиты для работы с потерянными собаками
const lostDogsUtils = {
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('ko-KR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },

    validateImage(file) {
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!file) {
            throw new Error('이미지를 선택해주세요');
        }

        if (!validTypes.includes(file.type)) {
            throw new Error('JPG, PNG, GIF 형식의 이미지만 업로드 가능합니다');
        }

        if (file.size > maxSize) {
            throw new Error('이미지 크기는 5MB 이하여야 합니다');
        }

        return true;
    }
};

// API для работы с потерянными собаками
const lostDogsApi = {
    async reportLostDog(formData) {
        const response = await fetch(`${API_BASE_URL}/report_lost_dog`, {
            method: 'POST',
            body: formData,
            mode: 'cors'
        });
        
        if (!response.ok) {
            throw new Error('서버 오류가 발생했습니다');
        }
        
        return await response.json();
    },
    
    async searchSimilarDogs(formData) {
        const response = await fetch(`${API_BASE_URL}/search_similar_dogs`, {
            method: 'POST',
            body: formData,
            mode: 'cors'
        });
        
        if (!response.ok) {
            throw new Error('서버 오류가 발생했습니다');
        }
        
        return await response.json();
    },
    
    async markDogFound(dogId) {
        const response = await fetch(`${API_BASE_URL}/mark_dog_found/${dogId}`, {
            method: 'POST',
            mode: 'cors'
        });
        
        if (!response.ok) {
            throw new Error('서버 오류가 발생했습니다');
        }
        
        return await response.json();
    }
};

// UI компоненты
const lostDogsUI = {
    createSearchForm() {
        return `
            <div class="search-form">
                <h3>비슷한 강아지 찾기</h3>
                <form id="search-form">
                    <div class="form-group">
                        <label>사진 업로드</label>
                        <input type="file" name="image" accept="image/*" required>
                    </div>
                    
                    <div class="form-group">
                        <label>검색 반경 (km)</label>
                        <input type="number" name="radius" value="50" min="1" max="100">
                    </div>
                    
                    <button type="submit" class="search-button">
                        <span class="icon">🔍</span> 검색하기
                    </button>
                </form>
            </div>
        `;
    },
    
    createReportForm() {
        return `
            <div class="lost-dog-form">
                <h3>잃어버린 강아지 등록</h3>
                <form id="lost-dog-form">
                    <div class="form-group">
                        <label>사진 업로드</label>
                        <input type="file" name="image" accept="image/*" required>
                    </div>
                    
                    <div class="form-group">
                        <label>실종 장소</label>
                        <input type="text" name="location" placeholder="주소를 입력하세요" required>
                    </div>
                    
                    <div class="form-group">
                        <label>실종 날짜</label>
                        <input type="date" name="date_lost" required>
                    </div>
                    
                    <div class="form-group">
                        <label>연락처</label>
                        <input type="text" name="contact_info" placeholder="전화번호나 이메일" required>
                    </div>
                    
                    <div class="form-group">
                        <label>추가 정보</label>
                        <textarea name="additional_info" placeholder="특징이나 기타 정보"></textarea>
                    </div>
                    
                    <button type="submit" class="submit-button">
                        <span class="icon">📝</span> 등록하기
                    </button>
                </form>
            </div>
        `;
    },
    
    createSimilarDogsResults(results) {
        if (!results.similar_dogs || results.similar_dogs.length === 0) {
            return `
                <div class="no-results">
                    <p>유사한 강아지를 찾을 수 없습니다</p>
                </div>
            `;
        }
        
        return `
            <div class="similar-dogs-results">
                <h3>검색 결과</h3>
                <div class="dogs-grid">
                    ${results.similar_dogs.map(dog => `
                        <div class="dog-card" data-id="${dog.id}">
                            <img src="${dog.image_path}" alt="Similar dog" class="dog-image">
                            <div class="dog-info">
                                <div class="similarity-badge">
                                    ${Math.round(dog.similarity)}% 일치
                                </div>
                                <h4 class="breed">${dog.breed}</h4>
                                <p class="location">
                                    <span class="icon">📍</span> ${dog.location}
                                </p>
                                <p class="date">
                                    <span class="icon">📅</span> ${lostDogsUtils.formatDate(dog.date_lost)}
                                </p>
                                <div class="contact">
                                    <p class="contact-info">
                                        <span class="icon">📞</span> ${dog.contact_info}
                                    </p>
                                    ${dog.additional_info ? `
                                        <p class="additional-info">
                                            <span class="icon">ℹ️</span> ${dog.additional_info}
                                        </p>
                                    ` : ''}
                                </div>
                                <button class="mark-found-btn" onclick="lostDogsHandlers.markDogFound(${dog.id})">
                                    강아지를 찾았어요
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
};

// Обработчики событий
const lostDogsHandlers = {
    init() {
        this.initSearchForm();
        this.initReportForm();
    },

    initSearchForm() {
        const form = document.getElementById('search-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const resultsContainer = document.getElementById('search-results');
            const submitButton = form.querySelector('button[type="submit"]');
            
            try {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="icon">⌛</span> 검색 중...';
                
                if (resultsContainer) {
                    resultsContainer.innerHTML = `
                        <div class="loading">
                            <div class="spinner"></div>
                            <p>유사한 강아지를 검색하고 있습니다...</p>
                        </div>
                    `;
                }
                
                const results = await lostDogsApi.searchSimilarDogs(formData);
                if (resultsContainer) {
                    resultsContainer.innerHTML = lostDogsUI.createSimilarDogsResults(results);
                }
            } catch (error) {
                if (resultsContainer) {
                    resultsContainer.innerHTML = `
                        <div class="error">
                            <p>${error.message}</p>
                        </div>
                    `;
                }
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<span class="icon">🔍</span> 검색하기';
            }
        });
    },

    initReportForm() {
        const form = document.getElementById('lost-dog-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            
            try {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="icon">⌛</span> 등록 중...';
                
                const result = await lostDogsApi.reportLostDog(formData);
                alert('강아지 정보가 등록되었습니다.');
                form.reset();
            } catch (error) {
                alert(error.message);
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<span class="icon">📝</span> 등록하기';
            }
        });
    },

    async markDogFound(dogId) {
        if (!confirm('정말로 이 강아지를 찾았나요?')) {
            return;
        }
        
        try {
            await lostDogsApi.markDogFound(dogId);
            alert('강아지를 찾았다는 정보가 업데이트되었습니다.');
            
            // Обновляем результаты поиска
            const searchForm = document.getElementById('search-form');
            if (searchForm) {
                searchForm.dispatchEvent(new Event('submit'));
            }
        } catch (error) {
            alert(error.message);
        }
    }
};