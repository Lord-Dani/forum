<template>
    <div id="app" class="app-container">
        <header>
            <div class="container header-content">
                <div class="logo">IT Forum</div>
                <nav>
                    <ul>
                        <li>
                            <transition name="smiley-change" mode="out-in">
                                <a href="#" :key="currentSmiley" class="smiley-container">{{ currentSmiley }}</a>
                            </transition>
                        </li>

                        <li v-if="!state.isAuthenticated"><a href="/login">Войти</a></li>
                        <li v-if="!state.isAuthenticated"><a href="/register">Регистрация</a></li>
                        <li v-if="state.isAuthenticated"><a href="#" @click.prevent="state.showProfile = true">Мой профиль</a></li>
                        <li v-if="state.isAuthenticated"><a href="#" @click.prevent="logout">Выйти</a></li>
                        <!--админ-->
                        <li v-if="isAdmin">
                            <a href="/admin">Админ</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </header>

        <div class="container main-container">
            <main>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ state.currentCategory ? `Темы в категории "${state.currentCategory}"` : 'Последние обсуждения' }}</h2>
                        <div class="search-and-create">
                            <div class="search-container">
                                <input type="text" 
                                       class="form-control search-input" 
                                       placeholder="Поиск по ключевым словам..."
                                       v-model="state.searchQuery"
                                       @input="applyFilters">
                                <button class="btn btn-primary search-btn" @click="applyFilters">Найти</button>
                            </div>
                            <button class="btn btn-primary create-btn" @click="state.showNewTopic = true" v-if="state.isAuthenticated">Новая тема</button>
                        </div>
                    </div>
                    <div class="topic-list-container">
                        <ul class="topic-list">
                            <li class="topic-item" v-for="topic in state.filteredTopics" :key="topic.id">
                                <a href="#" class="topic-title" @click.prevent="showTopic(topic.id)">{{ topic.title }}</a>
                                <div class="topic-meta">
                                    <span>Автор: {{ topic.author }}, </span>
                                    <span>Категория: {{ topic.category }}, </span>
                                    <span>Ответов: {{ topic.replies }}, </span>
                                    <span>Последний ответ: {{ topic.lastReply }}</span>
                                </div>
                            </li>
                            <li v-if="state.filteredTopics.length === 0" class="topic-item">
                                <p>Тем не найдено. Попробуйте изменить критерии поиска.</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </main>

            <aside>
                <div class="card">
                    <h3 class="card-title">Категории</h3>
                    <ul class="category-list">
                        <li class="category-item" v-for="category in state.categories" :key="category.id">
                            <a href="#" class="category-link" 
                               @click.prevent="applyCategoryFilter(category)"
                               :class="{ 'active-category': state.currentCategoryId === category.id }">
                                {{ category.name }} ({{ category.count }})
                            </a>
                        </li>
                        <li class="category-item">
                            <a href="#" class="category-link" @click.prevent="clearFilters">Все категории</a>
                        </li>
                    </ul>
                </div>

                <div class="card active-users">
                    <h3 class="card-title">Активные пользователи</h3>
                    <ul class="user-list">
                        <li class="user-item" v-for="user in state.activeUsers" :key="user.id">
                            <div class="user-avatar">{{ user.initials }}</div>
                            <span>{{ user.name }}</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>

        <!-- New Topic Modal -->
        <div class="modal" v-if="state.showNewTopic" @click.self="state.showNewTopic = false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Новая тема</h2>
                        <button @click="state.showNewTopic = false" class="close-button">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger" v-if="state.newTopicError">{{ state.newTopicError }}</div>
                        <div class="form-group">
                            <label for="topic-title">Заголовок</label>
                            <input type="text" id="topic-title" class="form-control" v-model="state.newTopicForm.title">
                        </div>
                        <div class="form-group">
                            <label for="topic-category">Категория</label>
                            <select id="topic-category" class="form-control" v-model="state.newTopicForm.category">
                                <option v-for="category in state.categories" :value="category.id">{{ category.name }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="topic-content">Содержание</label>
                            <textarea id="topic-content" class="form-control" v-model="state.newTopicForm.content"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" @click="createTopic">Создать тему</button>
                    </div>
                    <div class="admin-actions" v-if="isAdmin">
                    <button @click="editTopic(topic.id)" class="btn btn-primary">Редактировать</button>
                    <button @click="deleteTopic(topic.id)" class="btn btn-danger">Удалить</button>
                    </div>
                    </div>
            </div>
        </div>

        <!-- Topic View Modal -->
        <div class="modal" v-if="state.currentTopic" @click.self="state.currentTopic = null">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">{{ state.currentTopic.title }}</h2>
                        <button @click="state.currentTopic = null" class="close-button">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="topic-post">
                            <div class="post-meta">
                                <span>Автор: {{ state.currentTopic.author }}</span>
                                <span>Дата: {{ state.currentTopic.date }}</span>
                            </div>
                            <div class="post-content">
                                <div v-if="!state.currentTopic.editing">
                                    {{ state.currentTopic.content }}
                                </div>
                                <div v-else class="edit-form">
                                    <textarea class="form-control" v-model="state.currentTopic.editContent"></textarea>
                                    <div class="topic-actions">
                                        <button class="btn btn-primary" @click="saveTopicEdit(state.currentTopic)">Сохранить</button>
                                        <button class="btn btn-outline" @click="cancelTopicEdit(state.currentTopic)">Отмена</button>
                                    </div>
                                </div>
                            </div>
                            <div class="topic-actions" v-if="state.isAuthenticated && (state.currentUser.name === state.currentTopic.author || isAdmin)">
                                <button class="btn btn-edit" @click="startTopicEdit(state.currentTopic)" v-if="!state.currentTopic.editing">Редактировать</button>
                                <button class="btn btn-delete" @click="deleteTopic(state.currentTopic)">Удалить</button>
                            </div>
                        </div>

                        <h3 class="replies-title">Ответы ({{ state.currentTopic.replies }})</h3>
                        <div class="reply-list">
                            <div class="reply-item" v-for="reply in state.currentTopic.repliesList" :key="reply.id">
                                <div class="reply-meta">
                                    <span>{{ reply.author }}</span>
                                    <span>{{ reply.date }}</span>
                                </div>
                                <div class="reply-content">
                                    <div v-if="!reply.editing">
                                        {{ reply.content }}
                                    </div>
                                    <div v-else class="edit-form">
                                        <textarea class="form-control" v-model="reply.editContent"></textarea>
                                        <div class="reply-actions">
                                            <button class="btn btn-primary" @click="saveReplyEdit(reply)">Сохранить</button>
                                            <button class="btn btn-outline" @click="cancelReplyEdit(reply)">Отмена</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="reply-actions" v-if="state.isAuthenticated && (state.currentUser.name === reply.author || isAdmin)">
                                    <button class="btn btn-edit" @click="startReplyEdit(reply)" v-if="!reply.editing">Редактировать</button>
                                    <button class="btn btn-delete" @click="deleteReply(reply)">Удалить</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group reply-form" v-if="state.isAuthenticated">
                            <label for="reply-content">Ваш ответ</label>
                            <textarea id="reply-content" class="form-control" v-model="state.replyContent"></textarea>
                            <button class="btn btn-primary" @click="addReply">Отправить</button>
                        </div>

                       
                        <div v-else class="login-prompt">
                            <p>Чтобы оставить ответ, пожалуйста <a href="/login">войдите</a> или <a href="/register">зарегистрируйтесь</a>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Modal -->
        <div class="modal" v-if="state.showProfile" @click.self="state.showProfile = false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Мой профиль</h2>
                        <button @click="state.showProfile = false" class="close-button">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="user-info">
                            <div class="user-avatar-large">{{ state.currentUser.initials }}</div>
                            <div class="user-details">
                                <h3>{{ state.currentUser.name }}</h3>
                                <p>Зарегистрирован: {{ state.currentUser.registrationDate }}</p>
                            </div>
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value">{{ state.currentUser.stats.topics }}</div>
                                <div class="stat-label">Темы</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ state.currentUser.stats.replies }}</div>
                                <div class="stat-label">Ответы</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" @click="logout">Выйти</button>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <div class="container">
                <p>IT Forum © 2025. Все права защищены.</p>
            </div>
        </footer>
    </div>
</template>

<script>
export default {
    data() {
        return {
            currentSmiley: '( ^ ω ^)',
            smileys: [
            '( ^ ω ^)', '(≧∇≦)ﾉ', 'ヽ(>∀<☆)ノ', 'o(≧▽≦)o', '(ﾉ◕ヮ◕)ﾉ*:･ﾟ✧',
                '(づ｡◕‿‿◕｡)づ', '(ﾉ´ヮ`)ﾉ*: ･ﾟ', '(●´ω｀●)', '(＾▽＾)', '(◕‿◕✿)',
                '（＾ｖ＾）', '(◠‿◠)', '(◠﹏◠)', '（；一_一）', '(＞﹏＜)',
                '(ノ°ο°)ノ', '(ﾟДﾟ;;)', '(☉_☉)', '(✿◠‿◠)', '(◑‿◐)',
                '(◕‿-)', '(─‿‿─)', '（ﾉ´д｀）', '(´･_･`)', '(´･ω･`)',
                '(；一_一)', '(；￣Д￣)', '(￣ω￣;)', '(；´Д｀)', '(´；ω；｀)',
                '(；◡；)', '(；^ω^）', '(ノಠ益ಠ)ノ', '(╯°□°）╯', '(ﾉಥ益ಥ)ﾉ',
                '(╥_╥)', '(Ｔ▽Ｔ)', '(ノ_<。)', '(μ_μ)', '(｡T ω T｡)',
                '(っ˘̩╭╮˘̩)っ', '(´；д；`)', '(´；Д；`)', '(ﾉ´･ω･)ﾉ', '(ノT∀T)ノ',
                '(╯︵╰,)', '(｡ŏ﹏ŏ)', '(｡•́︿•̀｡)', '(｡ﾉω＼｡)', '(｡╯︵╰｡)',
                '(っ´ω`)ﾉ(╥ω╥)', '(´• ω •`)', '(´･ω･｀)', '(｡•́︿•̀｡)', '(｡ŏ_ŏ)',
                '(｡•́︿•̀｡)', '(｡ﾉω＼｡)', '(｡╯︵╰｡)', '(っ´ω`)ﾉ(╥ω╥)', '(´• ω •`)',
                '（´・ω・｀）', '(´；д；｀)', '(´；Д；｀)', '(ﾉ´･ω･｀)ﾉ', '(ノT∀T)ノ',
                '(╯︵╰,)', '(｡ŏ﹏ŏ)', '(｡•́︿•̀｡)', '(｡ﾉω＼｡)', '(｡╯︵╰｡)',
                '(っ´ω`)ﾉ(╥ω╥)', '(´• ω •`)', '(´･ω･｀)', '(｡•́︿•̀｡)', '(｡ŏ_ŏ)',
                '(｡•́︿•̀｡)', '(｡ﾉω＼｡)', '(｡╯︵╰｡)', '(っ´ω`)ﾉ(╥ω╥)', '(´• ω •`)',
                '（´・ω・｀）', '(´；д；｀)', '(´；Д；｀)', '(ﾉ´･ω･｀)ﾉ', '(ノT∀T)ノ',
                '(╯︵╰,)', '(｡ŏ﹏ŏ)', '(｡•́︿•̀｡)', '(｡ﾉω＼｡)', '(｡╯︵╰｡)',
                '(っ´ω`)ﾉ(╥ω╥)', '(´• ω •`)', '(´･ω･｀)', '(｡•́︿•̀｡)', '(｡ŏ_ŏ)',
                '(｡•́︿•̀｡)', '(｡ﾉω＼｡)', '(｡╯︵╰｡)', '(っ´ω`)ﾉ(╥ω╥)', '(´• ω •`)',
                '（´・ω・｀）', '(´；д；｀)', '(´；Д；｀)', '(ﾉ´･ω･｀)ﾉ', '(ノT∀T)ノ'
            ],
            smileyInterval: null,
            state: {
                isAuthenticated: false,
                currentUser: null,
                topics: [],
                filteredTopics: [],
                categories: [],
                activeUsers: [],
                currentCategory: null,
                currentCategoryId: null,
                searchQuery: '',
                showLogin: false,
                showRegister: false,
                showNewTopic: false,
                showProfile: false,
                currentTopic: null,
                showTopicDetails: false,
                replyContent: '',
                loginForm: {
                    email: '',
                    password: ''
                },
                registerForm: {
                    name: '',
                    email: '',
                    password: '',
                    confirmPassword: ''
                },
                newTopicForm: {
                    title: '',
                    category: null,
                    content: ''
                },
                loginError: null,
                registerError: null,
                newTopicError: null
            }
        };
    },
    computed: {
        isAuthenticated() {
            return this.state.isAuthenticated;
        },
        isAdmin() {
            console.log('Проверка isAdmin, currentUser:', this.state.currentUser);
            return this.state.currentUser && this.state.currentUser.role === 'admin';
        }
    },
    mounted() {
        this.startSmileyRotation();
        this.checkAuthStatus();
        this.fetchInitialData();
        
        window.addEventListener('error', this.handleGlobalError);
    },
    beforeUnmount() {
        this.stopSmileyRotation();
        window.removeEventListener('error', this.handleGlobalError);
    },
    methods: {
        startSmileyRotation() {
            this.smileyInterval = setInterval(() => {
                const currentIndex = this.smileys.indexOf(this.currentSmiley);
                const nextIndex = (currentIndex + 1) % this.smileys.length;
                this.currentSmiley = this.smileys[nextIndex];
            }, 3000);
        },
        stopSmileyRotation() {
            if (this.smileyInterval) {
                clearInterval(this.smileyInterval);
                this.smileyInterval = null;
            }
        },
        handleGlobalError(event) {
            if (event.message && (
                event.message.includes('CSRF') || 
                event.message.includes('419') || 
                event.message.includes('token mismatch')
            )) {
                console.error('Обнаружена ошибка CSRF:', event);
                alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                window.location.reload();
                return;
            }
        },
        async checkAuthStatus() {
            try {
                const response = await fetch('/api/auth/status', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                console.log(this.currentUser);
                if (!response.ok) {
                    if (response.status === 419) {
                        console.error('Ошибка CSRFФпри проверке статуса аутентификации');
                        alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                        window.location.reload();
                        return;
                    }
                    
                    throw new Error(`Ошибка HTTP: ${response.status}`);
                    
                }
                
                const data = await response.json();
                
                console.log('Статус аутентификации:', data);
                
                this.state.isAuthenticated = data.authenticated;
                if (data.authenticated) {
                    this.state.currentUser = data.user;
                }
            } catch (error) {
                console.error('Ошибка при проверке статуса аутентификации:', error);
                if (error.message && error.message.includes('419')) {
                    alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                    window.location.reload();
                }
            }
        },
        async fetchInitialData() {
            try {
                const response = await fetch('/forum/initial-data', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    if (response.status === 419) {
                        console.error('Ошибка CSRF при загрузке данных');
                        alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                        window.location.reload();
                        return;
                    }
                    throw new Error(`Ошибка HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                console.log('Полученные данные:', data);
                
                this.state.topics = data.topics || [];
                this.state.filteredTopics = data.topics || [];
                this.state.categories = data.categories || [];
                this.state.activeUsers = data.activeUsers || [];
            } catch (error) {
                console.error('Ошибка при загрузке данных:', error);
                if (error.message && error.message.includes('419')) {
                    alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                    window.location.reload();
                }
            }
        },
        async logout() {
            try {
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                if (!csrfTokenElement) {
                    console.error('CSRF-токен не найден');
                    alert('Ошибка безопасности: CSRF-токен не найден. Страница будет перезагружена.');
                    window.location.reload();
                    return;
                }
                
                const csrfToken = csrfTokenElement.getAttribute('content');
                
                const response = await fetch('/logout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    window.location.href = '/';
                } else if (response.status === 419) {
                    console.error('Ошибка CSRF при выходе');
                    alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                    window.location.reload();
                } else {
                    console.error('Ошибка при выходе:', response.status);
                    alert('Произошла ошибка при выходе. Пожалуйста, попробуйте еще раз.');
                }
            } catch (error) {
                console.error('Ошибка при выходе из системы:', error);
                alert('Произошла ошибка при выходе. Пожалуйста, попробуйте еще раз.');
            }
        },
        applyFilters() {
            let filtered = [...this.state.topics];
            
            if (this.state.currentCategoryId) {
                filtered = filtered.filter(topic => {
                    const categoryObj = this.state.categories.find(c => c.id === this.state.currentCategoryId);
                    return topic.category === categoryObj.name;
                });
            }
            
            if (this.state.searchQuery) {
                const query = this.state.searchQuery.toLowerCase();
                filtered = filtered.filter(topic => 
                    topic.title.toLowerCase().includes(query) || 
                    topic.content.toLowerCase().includes(query)
                );
            }
            
            this.state.filteredTopics = filtered;
        },
        applyCategoryFilter(category) {
            this.state.currentCategoryId = category.id;
            this.state.currentCategory = category.name;
            this.applyFilters();
        },
        clearFilters() {
            this.state.currentCategoryId = null;
            this.state.currentCategory = null;
            this.state.searchQuery = '';
            this.state.filteredTopics = [...this.state.topics];
        },
        viewTopic(topic) {
            this.state.currentTopic = {...topic};
        },
        async showTopic(id) {
            try {
                const response = await fetch(`/forum/topics/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    if (response.status === 419) {
                        console.error('Ошибка CSRF при загрузке темы');
                        alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                        window.location.reload();
                        return;
                    }
                    throw new Error(`Ошибка HTTP: ${response.status}`);
                }
                
                const topic = await response.json();
                
                this.state.currentTopic = topic;
                this.state.showTopicDetails = true;
                
                if (!this.state.currentTopic.repliesList) {
                    this.state.currentTopic.repliesList = [];
                }
                
                console.log('Загруженная тема с ответами:', this.state.currentTopic);
            } catch (error) {
                console.error('Ошибка при загрузке темы:', error);
                if (error.message && error.message.includes('419')) {
                    alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                    window.location.reload();
                }
            }
        },
        async createTopic() {
            if (!this.state.newTopicForm.title || !this.state.newTopicForm.category || !this.state.newTopicForm.content) {
                this.state.newTopicError = 'Пожалуйста, заполните все поля';
                return;
            }
            
            if (!this.state.isAuthenticated) {
                this.state.newTopicError = 'Для создания темы необходимо войти в систему';
                return;
            }
            
            try {
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                
                if (!csrfTokenElement) {
                    console.error('CSRF-токен не найден');
                    this.state.newTopicError = 'Ошибка безопасности: CSRF-токен не найден';
                    return;
                }
                
                const csrfToken = csrfTokenElement.getAttribute('content');
                console.log('CSRF-токен:', csrfToken);
                
                const response = await fetch('/forum/topics', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        title: this.state.newTopicForm.title,
                        category: this.state.newTopicForm.category,
                        content: this.state.newTopicForm.content
                    })
                });
                
                if (!response.ok) {
                    if (response.status === 401) {
                        this.state.newTopicError = 'Для создания темы необходимо войти в систему';
                        return;
                    }
                    
                    if (response.status === 419) {
                        console.error('Ошибка CSRF при создании темы');
                        this.state.newTopicError = 'Ошибка безопасности: CSRF token mismatch. Страница будет перезагружена.';
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                        return;
                    }
                    
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('text/html')) {
                        this.state.newTopicError = 'Ошибка сервера: получен HTML вместо JSON. Проверьте авторизацию.';
                        return;
                    }
                }
                
                const data = await response.json();
                
                if (response.ok) {
                    this.state.topics.unshift(data.topic);
                    this.state.filteredTopics = [...this.state.topics];
                    this.state.showNewTopic = false;
                    this.state.newTopicForm = { title: '', category: null, content: '' };
                    this.state.newTopicError = null;
                } else {
                    this.state.newTopicError = data.message || 'Ошибка при создании темы';
                }
            } catch (error) {
                console.error('Ошибка при создании темы:', error);
                this.state.newTopicError = 'Ошибка при создании темы: ' + error.message;
                
                if (error.message && error.message.includes('419')) {
                    this.state.newTopicError = 'Ошибка безопасности: CSRF token mismatch. Страница будет перезагружена.';
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            }
        },
        startTopicEdit(topic) {
            topic.editing = true;
            topic.editContent = topic.content;
        },
        cancelTopicEdit(topic) {
            topic.editing = false;
            topic.editContent = null;
        },
        async saveTopicEdit(topic) {
            try {
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                if (!csrfTokenElement) {
                    console.error('CSRF-токен не найден');
                    alert('Ошибка безопасности: CSRF-токен не найден');
                    return;
                }
                
                const csrfToken = csrfTokenElement.getAttribute('content');
                const response = await fetch(`/forum/topics/${topic.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        content: topic.editContent
                    })
                });
                
                if (!response.ok) {
                    if (response.status === 419) {
                        console.error('Ошибка CSRF при обновлении темы');
                        alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                        window.location.reload();
                        return;
                    }
                }
                
                const data = await response.json();
                
                if (response.ok) {
                    topic.content = topic.editContent;
                    topic.editing = false;
                    topic.editContent = null;
                } else {
                    alert(data.message || 'Ошибка при обновлении темы');
                }
            } catch (error) {
                console.error('Ошибка при обновлении темы:', error);
                alert('Ошибка при обновлении темы');
                
                if (error.message && error.message.includes('419')) {
                    alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                    window.location.reload();
                }
            }
        },
        async deleteTopic(topic) {
            if (!confirm('Вы уверены, что хотите удалить эту тему?')) {
                return;
            }
            
            try {
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                if (!csrfTokenElement) {
                    console.error('CSRF-токен не найден');
                    alert('Ошибка безопасности: CSRF-токен не найден');
                    return;
                }
                
                const csrfToken = csrfTokenElement.getAttribute('content');
                const response = await fetch(`/forum/topics/${topic.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    if (response.status === 419) {
                        console.error('Ошибка CSRF при удалении темы');
                        alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                        window.location.reload();
                        return;
                    }
                }
                
                const data = await response.json();
                console.log('Прооооооверка', data)
                
                if (response.ok) {
                    this.state.topics = this.state.topics.filter(t => t.id !== topic.id);
                    this.state.filteredTopics = this.state.filteredTopics.filter(t => t.id !== topic.id);
                    this.state.currentTopic = null;
                } else {
                    alert(data.message || 'Ошибка при удалении темы');
                }
            } catch (error) {
                console.error('Ошибка при удалении темы:', error);
                alert('Ошибка при удалении темы');
                
                if (error.message && error.message.includes('419')) {
                    alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                    window.location.reload();
                }
            }
        },
        async addReply() {
            if (!this.state.replyContent) {
                alert('Пожалуйста, введите текст ответа');
                return;
            }
            
            try {
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                
                if (!csrfTokenElement) {
                    console.error('CSRF-токен не найден');
                    alert('Ошибка безопасности: CSRF-токен не найден');
                    return;
                }
                
                const csrfToken = csrfTokenElement.getAttribute('content');
                const response = await fetch(`/forum/topics/${this.state.currentTopic.id}/replies`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        content: this.state.replyContent
                    })
                });
                
                if (!response.ok) {
                    if (response.status === 419) {
                        console.error('Ошибка CSRF при добавлении ответа');
                        alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                        window.location.reload();
                        return;
                    }
                }
                
                const data = await response.json();
                
                if (response.ok) {
                    this.state.currentTopic.repliesList = this.state.currentTopic.repliesList || [];
                    this.state.currentTopic.repliesList.push(data.reply);
                    this.state.currentTopic.replies++;
                    this.state.currentTopic.lastReply = 'только что';
                    
                    const topicIndex = this.state.topics.findIndex(t => t.id === this.state.currentTopic.id);
                    if (topicIndex !== -1) {
                        this.state.topics[topicIndex].replies++;
                        this.state.topics[topicIndex].lastReply = 'только что';
                    }
                    
                    this.state.replyContent = '';
                } else {
                    alert(data.message || 'Ошибка при добавлении ответа');
                }
            } catch (error) {
                console.error('Ошибка при добавлении ответа:', error);
                alert('Ошибка при добавлении ответа: ' + error.message);
                
                if (error.message && error.message.includes('419')) {
                    alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                    window.location.reload();
                }
            }  
        },
        startReplyEdit(reply) {
            reply.editing = true;
            reply.editContent = reply.content;
        },
        cancelReplyEdit(reply) {
            reply.editing = false;
            reply.editContent = null;
        },
        async saveReplyEdit(reply) {
            try {
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                if (!csrfTokenElement) {
                    console.error('CSRF-токен не найден');
                    alert('Ошибка безопасности: CSRF-токен не найден');
                    return;
                }
                
                const csrfToken = csrfTokenElement.getAttribute('content');
                const response = await fetch(`/forum/replies/${reply.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        content: reply.editContent
                    })
                });
                
                if (!response.ok) {
                    if (response.status === 419) {
                        console.error('Ошибка CSRF при обновлении ответа');
                        alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                        window.location.reload();
                        return;
                    }
                }
                
                const data = await response.json();
                
                if (response.ok) {
                    reply.content = reply.editContent;
                    reply.editing = false;
                    reply.editContent = null;
                } else {
                    alert(data.message || 'Ошибка при обновлении ответа');
                }
            } catch (error) {
                console.error('Ошибка при обновлении ответа:', error);
                alert('Ошибка при обновлении ответа');
                
                if (error.message && error.message.includes('419')) {
                    alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                    window.location.reload();
                }
            }
        },
        async deleteReply(reply) {
            if (!confirm('Вы уверены, что хотите удалить этот ответ?')) {
                return;
            }
            
            try {
                const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
                if (!csrfTokenElement) {
                    console.error('CSRF-токен не найден');
                    alert('Ошибка безопасности: CSRF-токен не найден');
                    return;
                }
                
                const csrfToken = csrfTokenElement.getAttribute('content');
                const response = await fetch(`/forum/replies/${reply.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    if (response.status === 419) {
                        console.error('Ошибка CSRF при удалении ответа');
                        alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                        window.location.reload();
                        return;
                    }
                }
                
                const data = await response.json();
                
                if (response.ok) {
                    this.state.currentTopic.repliesList = this.state.currentTopic.repliesList.filter(r => r.id !== reply.id);
                    this.state.currentTopic.replies--;
                    
                    const topicIndex = this.state.topics.findIndex(t => t.id === this.state.currentTopic.id);
                    if (topicIndex !== -1) {
                        this.state.topics[topicIndex].replies--;
                    }
                } else {
                    alert(data.message || 'Ошибка при удалении ответа');
                }
            } catch (error) {
                console.error('Ошибка при удалении ответа:', error);
                alert('Ошибка при удалении ответа');
                
                if (error.message && error.message.includes('419')) {
                    alert('Произошла ошибка безопасности. Страница будет перезагружена для обновления сессии.');
                    window.location.reload();
                }
            }
        }
    }
};
</script>

<style>
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}

.app-container {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f8fa;
}

header {
    background-color: #343a40;
    color: white;
    padding: 15px 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 24px;
    font-weight: bold;
}

nav ul {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
}

nav ul li {
    margin-left: 20px;
}

nav ul li a {
    color: white;
    text-decoration: none;
    transition: color 0.3s;
}

nav ul li a:hover {
    color: #17a2b8;
}

.container {
    width: 100%;
    max-width: 1140px; /* Фиксированная максимальная ширина */
    margin: 0 auto;
    padding: 0 15px;
    box-sizing: border-box;
}

.main-container {
    display: flex;
    margin-top: 30px;
    margin-bottom: 30px;
    flex: 1;
}

main {
    flex: 1;
    margin-right: 20px;
    display: flex;
    flex-direction: column;
}

aside {
    width: 300px;
    flex-shrink: 0;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eaeaea;
}

.card-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
}

/* Улучшенная секция поиска и создания темы */
.search-and-create {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.search-container {
    display: flex;
    flex: 1;
    min-width: 200px;
    height: 42px; /* Фиксированная высота для поисковой формы */
}

.search-input {
    border-radius: 4px 0 0 4px;
    border: 1px solid #ced4da;
    padding: 8px 12px;
    flex-grow: 1;
    min-width: 100px;
    height: 100%; /* Занимает всю высоту контейнера */
    box-sizing: border-box;
}

.search-btn {
    border-radius: 0 4px 4px 0;
    min-width: 80px;
    height: 100%; /* Занимает всю высоту контейнера */
    padding: 0 16px; /* Горизонтальные отступы */
    display: flex;
    align-items: center;
    justify-content: center;
}

.create-btn {
    min-width: 120px;
    white-space: nowrap;
    height: 42px; /* Такая же высота как у поисковой формы */
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.3s;
    box-sizing: border-box;
}

.btn-primary {
    background-color: #3490dc;
    color: white;
}

.btn-primary:hover {
    background-color: #2779bd;
}

.btn-edit {
    background-color: #f8f9fa;
    color: #3490dc;
    border: 1px solid #3490dc;
    margin-right: 10px;
}

.btn-edit:hover {
    background-color: #e2e6ea;
}

.btn-delete {
    background-color: #f8f9fa;
    color: #e3342f;
    border: 1px solid #e3342f;
}

.btn-delete:hover {
    background-color: #e2e6ea;
}

.btn-outline {
    background-color: transparent;
    color: #6c757d;
    border: 1px solid #6c757d;
    margin-left: 10px;
}

.btn-outline:hover {
    background-color: #f8f9fa;
}

/* Контейнер для списка тем с фиксированной высотой и прокруткой */
.topic-list-container {
    flex: 1;
    overflow-y: auto;
    max-height: 500px; /* Фиксированная высота */
    border-top: 1px solid #eaeaea;
}

.topic-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.topic-item {
    padding: 15px 20px;
    border-bottom: 1px solid #eaeaea;
}

.topic-item:last-child {
    border-bottom: none;
}

.topic-title {
    display: block;
    font-size: 16px;
    font-weight: 500;
    color: #3490dc;
    margin-bottom: 5px;
    text-decoration: none;
}

.topic-meta {
    font-size: 14px;
    color: #6c757d;
}

.category-list {
    list-style: none;
    padding: 15px 20px;
    margin: 0;
}

.category-item {
    margin-bottom: 10px;
}

.category-item:last-child {
    margin-bottom: 0;
}

.category-link {
    color: #3490dc;
    text-decoration: none;
    display: block;
    padding: 5px 0;
    transition: color 0.3s;
}

.category-link:hover {
    color: #2779bd;
}

.active-category {
    font-weight: bold;
    color: #2779bd;
}

.active-users {
    margin-top: 20px;
}

.user-list {
    list-style: none;
    padding: 15px 20px;
    margin: 0;
}

.user-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.user-item:last-child {
    margin-bottom: 0;
}

.user-avatar {
    width: 30px;
    height: 30px;
    background-color: #3490dc;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 10px;
    font-size: 12px;
}

/* Модальные окна */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    z-index: 1000;
    overflow-y: auto;
    padding: 20px;
    align-items: flex-start;
    justify-content: center;
}

.modal-dialog {
    width: 100%;
    max-width: 600px;
    margin: 30px auto;
    position: relative;
}

.modal-dialog.modal-lg {
    max-width: 800px;
}

.modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eaeaea;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.close-button {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    max-height: calc(100vh - 200px);
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #eaeaea;
    display: flex;
    justify-content: flex-end;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 16px;
}

textarea.form-control {
    min-height: 150px;
    resize: vertical;
}

.alert {
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.topic-actions, .reply-actions {
    margin-top: 15px;
    display: flex;
}

.user-info {
    display: flex;
    align-items: center;
    margin-bottom: 25px;
}

.user-avatar-large {
    width: 70px;
    height: 70px;
    background-color: #3490dc;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 20px;
    font-size: 28px;
}

.user-details h3 {
    margin: 0 0 5px 0;
    font-size: 22px;
}

.user-details p {
    margin: 0;
    color: #6c757d;
}

.stats-grid {
    display: flex;
    margin-bottom: 25px;
    gap: 15px;
}

.stat-item {
    flex: 1;
    text-align: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #3490dc;
}

.stat-label {
    font-size: 16px;
    color: #6c757d;
    margin-top: 8px;
}

/* Стили для темы и ответов */
.topic-post {
    margin-bottom: 30px;
}

.post-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    color: #6c757d;
}

.post-content {
    margin-bottom: 20px;
    line-height: 1.6;
}

.replies-title {
    margin: 30px 0 15px;
    font-size: 20px;
    font-weight: 600;
}

.reply-list {
    margin-bottom: 30px;
}

.reply-item {
    padding: 20px 0;
    border-bottom: 1px solid #eaeaea;
}

.reply-item:last-child {
    border-bottom: none;
}

.reply-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    color: #6c757d;
}

.reply-content {
    margin-bottom: 15px;
    line-height: 1.5;
}

.reply-form {
    margin-top: 30px;
}

.login-prompt {
    margin-top: 25px;
    text-align: center;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.login-prompt a {
    color: #3490dc;
    text-decoration: none;
    font-weight: 500;
}

.login-prompt a:hover {
    text-decoration: underline;
}

footer {
    background-color: #343a40;
    color: white;
    padding: 25px 0;
    text-align: center;
    margin-top: auto;
}

/* Стилизация скроллбара */
.topic-list-container::-webkit-scrollbar {
    width: 8px;
}

.topic-list-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.topic-list-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.topic-list-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Адаптивная верстка */
@media (max-width: 992px) {
    .main-container {
        flex-direction: column;
    }
    
    main {
        margin-right: 0;
        margin-bottom: 20px;
    }
    
    aside {
        width: 100%;
    }
    
    .modal-dialog, .modal-dialog.modal-lg {
        max-width: 95%;
    }
}

@media (max-width: 768px) {
    .search-and-create {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-container {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .create-btn {
        width: 100%;
    }
    
    .post-meta, .reply-meta {
        flex-direction: column;
    }
    
    .post-meta span, .reply-meta span {
        margin-bottom: 5px;
    }
    
    .stats-grid {
        flex-direction: column;
    }
    
    .stat-item {
        margin-bottom: 10px;
    }
    
    .user-info {
        flex-direction: column;
        text-align: center;
    }
    
    .user-avatar-large {
        margin: 0 auto 15px;
    }
    
    .modal {
        padding: 10px;
    }
    
    .modal-dialog {
        margin: 10px auto;
    }
    
    .modal-body {
        max-height: calc(100vh - 150px);
        padding: 15px;
    }
}

@media (max-width: 576px) {
    .header-content {
        flex-direction: column;
    }
    
    .logo {
        margin-bottom: 10px;
    }
    
    nav ul {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    nav ul li {
        margin: 5px 10px;
    }
    
    .topic-meta {
        flex-direction: column;
    }
    
    .topic-meta span {
        display: block;
        margin-bottom: 3px;
    }
    
    .modal-title {
        font-size: 18px;
    }
    
    .form-control {
        font-size: 14px;
    }
}

/* Анимация смены смайлов */
.smiley-change-enter-active, 
.smiley-change-leave-active {
    transition: all 0.5s ease;
}

.smiley-change-enter-from {
    opacity: 0;
    transform: translateY(-10px) scale(0.8);
}

.smiley-change-leave-to {
    opacity: 0;
    transform: translateY(10px) scale(0.8);
}

/* Фиксированная ширина и высота для смайлов */
.smiley-container {
    display: inline-flex;
    min-width: 100px;
    height: 24px;  /* Уменьшил высоту с 40px до 24px */
    align-items: center;
    justify-content: center;
    text-align: center;
    vertical-align: middle;  /* Добавил вертикальное выравнивание */
}


</style>