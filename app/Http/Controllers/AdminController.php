<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Topic;
use App\Models\Reply;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Отображение админ-панели
     */
    public function dashboard()
    {
        $users = User::all();
        $topics = Topic::latest()->take(10)->get();
        $replies = Reply::latest()->take(10)->get();
        
        return response()->json([
            'users' => $users,
            'topics' => $topics,
            'replies' => $replies,
            'stats' => [
                'users_count' => User::count(),
                'topics_count' => Topic::count(),
                'replies_count' => Reply::count(),
                'blocked_users' => User::where('is_blocked', true)->count()
            ]
        ]);
    }
    
    /**
     * Получение списка пользователей
     */
    public function getUsers()
    {
        $users = User::all();
        return response()->json(['users' => $users]);
    }
    
    /**
     * Блокировка/разблокировка пользователя
     */
    public function toggleUserBlock($id)
    {
        $user = User::findOrFail($id);
        $user->is_blocked = !$user->is_blocked;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => $user->is_blocked ? 'Пользователь заблокирован' : 'Пользователь разблокирован',
            'user' => $user
        ]);
    }
    
    /**
     * Редактирование темы
     */
    public function updateTopic(Request $request, $id)
    {
        $topic = Topic::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id'
        ]);
        
        $topic->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Тема обновлена',
            'topic' => $topic
        ]);
    }
    
    /**
     * Удаление темы
     */
    public function deleteTopic($id)
    {
        $topic = Topic::findOrFail($id);
        $topic->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Тема удалена'
        ]);
    }
    
    /**
     * Редактирование ответа
     */
    public function updateReply(Request $request, $id)
    {
        $reply = Reply::findOrFail($id);
        
        $validated = $request->validate([
            'content' => 'required|string'
        ]);
        
        $reply->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Ответ обновлен',
            'reply' => $reply
        ]);
    }
    
    /**
     * Удаление ответа
     */
    public function deleteReply($id)
    {
        $reply = Reply::findOrFail($id);
        $reply->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Ответ удален'
        ]);
    }
}
