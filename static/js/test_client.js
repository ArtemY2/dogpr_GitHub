const API_URL = 'http://localhost:5001';

async function testServer() {
    try {
        const response = await fetch(`${API_URL}/test`);
        const data = await response.json();
        console.log('Server response:', data);
        return data;
    } catch (error) {
        console.error('Error testing server:', error);
        throw error;
    }
}

// Тестируем подключение
testServer()
    .then(data => console.log('Test successful:', data))
    .catch(error => console.error('Test failed:', error));