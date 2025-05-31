<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Topic;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ForumController extends Controller
{
    /**
     * Получить начальные данные для форума
     */
    public function getInitialData()
    {
        // Логируем запрос для отладки
        Log::info('Запрос начальных данных форума');
        
        // Получаем категории
        $categories = Category::all()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'count' => $category->topics()->count()
            ];
        });
        
        // Получаем темы
        $topics = Topic::with('user', 'category')->latest()->get()->map(function ($topic) {
            return [
                'id' => $topic->id,
                'title' => $topic->title,
                'content' => $topic->content,
                'author' => $topic->user->name,
                'category' => $topic->category->name,
                'replies' => $topic->replies()->count(),
                'lastReply' => $topic->replies()->latest()->first() 
                    ? $topic->replies()->latest()->first()->created_at->diffForHumans() 
                    : 'Нет ответов',
                'date' => $topic->created_at->format('d.m.Y H:i')
            ];
        });
        
        // Получаем активных пользователей
        $activeUsers = User::orderBy('last_activity', 'desc')
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'initials' => strtoupper(substr($user->name, 0, 2))
                ];
            });
        
        // Логируем результат для отладки
        Log::info('Данные форума получены', [
            'categories_count' => $categories->count(),
            'topics_count' => $topics->count(),
            'active_users_count' => $activeUsers->count()
        ]);
        
        return response()->json([
            'categories' => $categories,
            'topics' => $topics,
            'activeUsers' => $activeUsers
        ]);
    }
    
    /**
     * Создать новую тему
     */
    public function createTopic(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|exists:categories,id',
            'content' => 'required|string'
        ]);
        
        $topic = new Topic();
        $topic->title = $request->title;
        $topic->content = $request->content;
        $topic->user_id = Auth::id();
        $topic->category_id = $request->category;
        $topic->save();
        
        return response()->json([
            'success' => true,
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'content' => $topic->content,
                'author' => Auth::user()->name,
                'category' => Category::find($request->category)->name,
                'replies' => 0,
                'lastReply' => 'Нет ответов',
                'date' => $topic->created_at->format('d.m.Y H:i')
            ]
        ]);
    }
    
    /**
     * Обновить тему
     */
    public function updateTopic(Request $request, $id)
    {
        $topic = Topic::findOrFail($id);
        
        // Проверка прав доступа
        if (!(Auth::user()->role === 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав на редактирование этой темы'
            ], 403);
        }
        
        $request->validate([
            'content' => 'required|string'
        ]);
        
        $topic->content = $request->content;
        $topic->save();
        
        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Получить тему с ответами
     */
    public function getTopic($id)
    {
        try {
            $topic = Topic::with(['user', 'category', 'replies.user'])->findOrFail($id);
            
            $formattedTopic = [
                'id' => $topic->id,
                'title' => $topic->title,
                'content' => $topic->content,
                'author' => $topic->user->name,
                'category' => $topic->category->name,
                'category_id' => $topic->category_id,
                'replies' => $topic->replies->count(),
                'date' => $topic->created_at->format('d.m.Y H:i'),
                'user_id' => $topic->user_id,
                // Добавляем список ответов
                'repliesList' => $topic->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'content' => $reply->content,
                        'author' => $reply->user->name,
                        'date' => $reply->created_at->format('d.m.Y H:i'),
                        'user_id' => $reply->user_id
                    ];
                })
            ];
            
            return response()->json($formattedTopic);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении темы: ' . $e->getMessage());
            return response()->json(['error' => 'Тема не найдена'], 404);
        }
    }

    
    /**
     * Удалить тему
     */
    public function deleteTopic($id)
    {
        $topic = Topic::findOrFail($id);
        
        // Проверка прав доступа
        if (!(Auth::user()->role === 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав на удаление этой темы'
            ], 403);
        }
        
        // Удаляем все ответы к теме
        $topic->replies()->delete();
        
        // Удаляем тему
        $topic->delete();
        
        return response()->json([
            'success' => true
        ]);
    }
    
    /**
     * Добавить ответ к теме
     */
    public function addReply(Request $request, $id)
    {
        $topic = Topic::findOrFail($id);
        
        $request->validate([
            'content' => 'required|string'
        ]);
        
        $reply = new Reply();
        $reply->content = $request->content;
        $reply->user_id = Auth::id();
        $reply->topic_id = $topic->id;
        $reply->save();
        
        return response()->json([
            'success' => true,
            'reply' => [
                'id' => $reply->id,
                'content' => $reply->content,
                'author' => Auth::user()->name,
                'date' => $reply->created_at->format('d.m.Y H:i'),
                'user_id' => Auth::id()
            ]
        ]);
    }
    
    /**
     * Обновить ответ
     */
    public function updateReply(Request $request, $id)
    {
        $reply = Reply::findOrFail($id);
        
        // Проверка прав доступа
        if (!(Auth::user()->role === 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав на редактирование этого ответа'
            ], 403);
        }
        
        $request->validate([
            'content' => 'required|string'
        ]);
        
        $reply->content = $request->content;
        $reply->save();
        
        return response()->json([
            'success' => true
        ]);
    }
    
    /**
     * Удалить ответ
     */
    public function deleteReply($id)
    {
        $reply = Reply::findOrFail($id);
        
        // Проверка прав доступа
        if (!(Auth::user()->role === 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав на удаление этого ответа'
            ], 403);
        }
        
        $reply->delete();
        
        return response()->json([
            'success' => true
        ]);
    }
}
