const API_BASE_URL = 'http://220.72.117.232:5001';

const BREED_MAP = {
    'Chihuahua': '1',
    'Yorkshire_terrier': '2',
    'Maltese': '3',
    'Jindo': '4',
    'Border_Collie': '5',
    'Pomeranian': '6',
    'Poodle': '7'
};

const utils = {
    getBreedUrl(breedKey, type) {
        if (type === 'image') {
            return `/static/breeds/${BREED_MAP[breedKey]}/main.jpg`;
        } else {
            return `/breeds.php?breed=${breedKey}`;
        }
    },

    formatBreedName(breed) {
        if (!breed) return '알 수 없는 견종';
        return breed.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },

    formatProbability(probability) {
        if (typeof probability !== 'number' || isNaN(probability)) {
            return '0.0';
        }
        const value = Math.max(0, Math.min(100, probability));
        return value.toFixed(1);
    },

    validateImage(file) {
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024;

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

const ui = {
    showLoading(element) {
        element.innerHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <p>분석 중입니다...</p>
            </div>
        `;
    },

    showError(element, message) {
        element.innerHTML = `
            <div class="error">
                <p class="error-message">${message}</p>
                <button onclick="uploadHandlers.initializeUpload()" class="retry-btn">다시 시도</button>
            </div>
        `;
    },

    // В объекте ui обновляем функцию createResultHTML

    createResultHTML(result) {
        const { predictions, similar_images, image_path } = result;
        const topBreed = predictions[0] || { breed: 'Unknown', probability: 0 };
    
        return `
            <div class="result-container">
                <div class="comparison-section">
                    <div class="comparison-card">
                        <h3 class="card-title">업로드된 이미지</h3>
                        <div class="image-wrapper">
                            <img src="${image_path}" alt="Uploaded dog" class="comparison-image">
                        </div>
                    </div>
    
                    <div class="comparison-card">
                        <h3 class="card-title">판별된 견종</h3>
                        <div class="image-wrapper">
                            <img src="${utils.getBreedUrl(topBreed.breed, 'image')}" 
                                 alt="${utils.formatBreedName(topBreed.breed)}" 
                                 class="comparison-image">
                        </div>
                        <div class="breed-info">
                            <div class="breed-name">${utils.formatBreedName(topBreed.breed)}</div>
                            <div class="probability-bar-container">
                                <div class="probability-bar">
                                    <div class="progress" style="width: ${utils.formatProbability(topBreed.probability)}%"></div>
                                    <span class="probability-value">${utils.formatProbability(topBreed.probability)}%</span>
                                </div>
                            </div>
                            <div class="breed-buttons">
                                <a href="breeds.php?breed=${topBreed.breed}" class="learn-more-btn">
                                    자세히 보기
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
    
                ${Array.isArray(similar_images) && similar_images.length > 0 ? `
                    <div class="similar-section">
                        <h3 class="section-title">비슷한 실종견</h3>
                        <div class="similar-grid">
                            ${similar_images.map(similar => `
                                <div class="similar-card">
                                    <div class="image-wrapper">
                                        <img src="/static/uploads/${similar.image_path}" 
                                             alt="${similar.name}"
                                             class="similar-image">
                                    </div>
                                    <div class="similar-info">
                                        <div class="pet-name">${similar.name}</div>
                                        <div class="similarity-value">${(similar.similarity * 100).toFixed(1)}% 유사</div>
                                        <div class="pet-details">
                                            <p><strong>나이:</strong> ${similar.age}</p>
                                            <p><strong>특징:</strong> ${similar.features}</p>
                                            <p><strong>연락처:</strong> ${similar.owner_phone}</p>
                                        </div>
                                        <button 
                                            class="details-btn"
                                            onclick="showPetDetails(${similar.id})"
                                        >
                                             자세히 보기
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }
};

const api = {
    async predictBreed(formData) {
        try {
            const response = await fetch(`${API_BASE_URL}/predict`, {
                method: 'POST',
                body: formData,
                mode: 'cors',
                credentials: 'omit'
            });

            if (!response.ok) {
                throw new Error('서버 오류가 발생했습니다');
            }

            const result = await response.json();
            console.log('Prediction result:', result); // Для отладки
            return result;
        } catch (error) {
            console.error('Prediction failed:', error);
            throw new Error('분석 중 오류가 발생했습니다');
        }
    }
};

async function predictBreed(formData) {
    try {
        const response = await fetch(`${API_BASE_URL}/predict`, {
            method: 'POST',
            body: formData,
            mode: 'cors',
            credentials: 'omit'
        });

        if (!response.ok) {
            throw new Error('서버 오류가 발생했습니다');
        }

        return await response.json();
    } catch (error) {
        console.error('Prediction failed:', error);
        throw new Error('분석 중 오류가 발생했습니다');
    }
}

function prevImage(petId) {
    const card = document.querySelector(`.similar-card[data-pet-id="${petId}"]`);
    const slider = card.querySelector('.image-slider');
    const images = card.querySelectorAll('.similar-image');
    let currentIndex = parseInt(slider.dataset.current);
    
    // Скрываем текущее изображение
    images[currentIndex].classList.add('hidden');
    
    // Вычисляем индекс предыдущего изображения
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    
    // Показываем предыдущее изображение
    images[currentIndex].classList.remove('hidden');
    slider.dataset.current = currentIndex;
}

function nextImage(petId) {
    const card = document.querySelector(`.similar-card[data-pet-id="${petId}"]`);
    const slider = card.querySelector('.image-slider');
    const images = card.querySelectorAll('.similar-image');
    let currentIndex = parseInt(slider.dataset.current);
    
    // Скрываем текущее изображение
    images[currentIndex].classList.add('hidden');
    
    // Вычисляем индекс следующего изображения
    currentIndex = (currentIndex + 1) % images.length;
    
    // Показываем следующее изображение
    images[currentIndex].classList.remove('hidden');
    slider.dataset.current = currentIndex;
}

async function showPetDetails(petId) {
    try {
        const response = await fetch(`${API_BASE_URL}//${petId}`);
        if (!response.ok) throw new Error('Failed to fetch pet details');
        
        const details = await response.json();
        
        const modal = document.createElement('div');
        modal.className = 'pet-details-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <div class="pet-gallery">
                    <div class="main-image">
                        <img src="/static/breeds/${details.folder}/${details.main_photo}" 
                             alt="${details.name}" 
                             id="mainImage">
                        <div class="gallery-nav">
                            <button class="gallery-btn prev" onclick="prevGalleryImage()">❮</button>
                            <button class="gallery-btn next" onclick="nextGalleryImage()">❯</button>
                        </div>
                    </div>
                    <div class="thumbnail-strip">
                        ${details.additional_photos ? details.additional_photos.map((photo, index) => `
                            <img src="/static/breeds/${details.folder}/${photo}" 
                                 alt="Additional photo"
                                 onclick="showGalleryImage(${index})"
                                 class="thumbnail">
                        `).join('') : ''}
                    </div>
                </div>
                <div class="pet-info">
                    <h2>${details.name}</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>나이:</strong> ${details.age}
                        </div>
                        <div class="info-item">
                            <strong>특징:</strong> ${details.features}
                        </div>
                        <div class="info-item">
                            <strong>연락처:</strong> 
                            <a href="tel:${details.owner_phone}" class="phone-link">
                                ${details.owner_phone}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const closeBtn = modal.querySelector('.close-modal');
        closeBtn.onclick = () => modal.remove();
        
        modal.onclick = (e) => {
            if (e.target === modal) modal.remove();
        };
    } catch (error) {
        console.error('Error fetching pet details:', error);
    }
}

const uploadHandlers = {
    initializeUpload() {
        const uploadArea = document.getElementById('upload-area');
        const imageInput = document.getElementById('image-upload');
        const uploadForm = document.getElementById('upload-form');
        const resultDiv = document.getElementById('result');

        if (!uploadArea || !imageInput || !uploadForm || !resultDiv) {
            console.error('Required elements not found');
            return;
        }

        const handleAreaClick = () => imageInput.click();
        
        uploadArea.removeEventListener('click', handleAreaClick);
        uploadArea.addEventListener('click', handleAreaClick);

        function handleImageUpload(file) {
            try {
                if (!file) return;
        
                // Проверяем файл
                utils.validateImage(file);
                
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    uploadArea.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" class="preview-image">
                        <div class="preview-overlay">
                            <div class="change-image-text">
                                <span class="upload-icon">📷</span>
                                <p>클릭하여 다른 사진 선택</p>
                            </div>
                        </div>
                    `;
                    uploadArea.classList.add('has-preview');
                };
        
                reader.readAsDataURL(file);
            } catch (error) {
                console.error('Error uploading image:', error);
                ui.showError(resultDiv, error.message);
            }
        }
        

        const handleFileChange = (e) => {
            if (e.target.files && e.target.files.length > 0) {
                handleImageUpload(e.target.files[0]);
            }
        };

        imageInput.removeEventListener('change', handleFileChange);
        imageInput.addEventListener('change', handleFileChange);

        const preventDefault = (e) => {
            e.preventDefault();
            e.stopPropagation();
        };

        const handleDragEnter = () => uploadArea.classList.add('drag-over');
        const handleDragLeave = () => uploadArea.classList.remove('drag-over');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.removeEventListener(eventName, preventDefault);
            uploadArea.addEventListener(eventName, preventDefault);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.removeEventListener(eventName, handleDragEnter);
            uploadArea.addEventListener(eventName, handleDragEnter);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.removeEventListener(eventName, handleDragLeave);
            uploadArea.addEventListener(eventName, handleDragLeave);
        });

        const handleDrop = (e) => {
            const file = e.dataTransfer.files[0];
            if (file) {
                handleImageUpload(file);
            }
        };

        uploadArea.removeEventListener('drop', handleDrop);
        uploadArea.addEventListener('drop', handleDrop);

        const handleSubmit = async (event) => {
            event.preventDefault();
            const file = imageInput.files?.[0];
            
            try {
                if (!file) {
                    throw new Error('이미지를 선택해주세요');
                }

                ui.showLoading(resultDiv);

                const formData = new FormData();
                formData.append('image', file);

                const result = await api.predictBreed(formData);
                resultDiv.innerHTML = ui.createResultHTML(result);
                resultDiv.scrollIntoView({ behavior: 'smooth' });
                
            } catch (error) {
                console.error('Error:', error);
                ui.showError(resultDiv, error.message);
            }
        };

        uploadForm.removeEventListener('submit', handleSubmit);
        uploadForm.addEventListener('submit', handleSubmit);

        const selectButton = document.querySelector('.primary-button');
        if (selectButton) {
            selectButton.onclick = (e) => {
                e.preventDefault();
                imageInput.click();
            };
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    uploadHandlers.initializeUpload();
});
