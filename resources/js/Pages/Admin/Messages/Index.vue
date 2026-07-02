<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    conversations: Array,
    stats: Object
});

const selectedId = ref(props.conversations[0]?.id || null);
const activeChat = ref(props.conversations.find(c => c.id === selectedId.value));
const newMessage = ref('');

const selectChat = (id) => {
    selectedId.value = id;
    activeChat.value = props.conversations.find(c => c.id === id);
};

const sendMessage = () => {
    if (!newMessage.value.trim()) return;
    activeChat.value.messages.push({
        id: Date.now(),
        sender: 'admin',
        text: newMessage.value,
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    });
    newMessage.value = '';
};
</script>

<template>
    <Head title="Support Center" />

    <AdminLayout>
        <div class="h-[calc(100vh-180px)] flex flex-col gap-6 animate-in fade-in duration-700">
            <!-- Header Stats -->
            <div class="grid grid-cols-4 gap-6 shrink-0">
                <div v-for="stat in [
                    { label: 'Open Tickets', value: stats.open_tickets, color: 'text-white' },
                    { label: 'Resolved', value: stats.closed_tickets, color: 'text-green-400' },
                    { label: 'Avg. Response', value: stats.avg_response, color: 'text-admin-modern' },
                    { label: 'Active Chats', value: stats.active_chats, color: 'text-blue-400' }
                ]" :key="stat.label" class="bg-zinc-900/40 backdrop-blur-md border border-white/5 p-5 rounded-[2rem]">
                    <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">{{ stat.label }}</p>
                    <p class="text-xl font-black" :class="stat.color">{{ stat.value }}</p>
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="flex-1 flex gap-6 overflow-hidden">
                <!-- Conversations List -->
                <div class="w-80 bg-zinc-900/40 backdrop-blur-md border border-white/5 rounded-[2.5rem] flex flex-col overflow-hidden">
                    <div class="p-6 border-b border-white/5">
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">Inbox</h3>
                    </div>
                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div 
                            v-for="chat in conversations" 
                            :key="chat.id"
                            @click="selectChat(chat.id)"
                            :class="[selectedId === chat.id ? 'bg-white/5 border-r-2 border-admin-modern' : 'hover:bg-white/[0.02]']"
                            class="p-5 cursor-pointer transition-all border-b border-white/5 flex gap-4 relative"
                        >
                            <div class="relative shrink-0">
                                <div class="w-12 h-12 rounded-2xl bg-zinc-800 flex items-center justify-center font-black text-white border border-white/10">
                                    {{ chat.user.name.charAt(0) }}
                                </div>
                                <div 
                                    :class="chat.user.status === 'online' ? 'bg-green-500' : 'bg-zinc-600'" 
                                    class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-4 border-zinc-900"
                                ></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-xs font-black text-white truncate">{{ chat.user.name }}</p>
                                    <span class="text-[10px] text-zinc-500 uppercase">{{ chat.time }}</span>
                                </div>
                                <p class="text-xs text-zinc-500 truncate">{{ chat.last_message }}</p>
                            </div>
                            <div v-if="chat.unread > 0" class="absolute top-5 right-5 w-5 h-5 bg-admin-modern text-black rounded-lg flex items-center justify-center text-[10px] font-black shadow-lg shadow-admin-modern/20">
                                {{ chat.unread }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Window -->
                <div class="flex-1 bg-zinc-900/40 backdrop-blur-md border border-white/5 rounded-[3rem] flex flex-col overflow-hidden relative">
                    <div v-if="activeChat" class="flex flex-col h-full">
                        <!-- Top Bar -->
                        <div class="p-6 border-b border-white/5 flex items-center justify-between bg-zinc-950/20">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-zinc-800 flex items-center justify-center font-black text-white border border-white/10">
                                    {{ activeChat.user.name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="text-sm font-black text-white tracking-tight">{{ activeChat.user.name }}</p>
                                    <div class="flex items-center gap-2">
                                        <span :class="activeChat.user.status === 'online' ? 'text-green-400' : 'text-zinc-500'" class="text-[10px] font-black uppercase tracking-widest">
                                            {{ activeChat.user.status }}
                                        </span>
                                        <span class="w-1 h-1 bg-zinc-700 rounded-full"></span>
                                        <span class="text-[10px] font-black text-admin-modern uppercase tracking-widest">Order #1245</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl text-zinc-400 transition-all border border-white/5">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                </button>
                                <button class="bg-red-500/10 hover:bg-red-500/20 text-red-400 px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-red-500/20 transition-all">
                                    Close Ticket
                                </button>
                            </div>
                        </div>

                        <!-- Messages Scroll -->
                        <div class="flex-1 overflow-y-auto p-8 space-y-8 custom-scrollbar bg-gradient-to-b from-transparent to-black/20">
                            <div v-for="msg in activeChat.messages" :key="msg.id" 
                                :class="[msg.sender === 'admin' ? 'justify-end' : 'justify-start']"
                                class="flex"
                            >
                                <div 
                                    :class="[msg.sender === 'admin' ? 'bg-admin-modern text-black rounded-tr-none' : 'bg-white/5 text-white rounded-tl-none']"
                                    class="max-w-[70%] p-5 rounded-[2rem] relative group"
                                >
                                    <p class="text-sm font-medium leading-relaxed">{{ msg.text }}</p>
                                    <span 
                                        :class="[msg.sender === 'admin' ? 'text-black/50' : 'text-zinc-500']"
                                        class="text-[9px] font-black uppercase mt-2 block text-right"
                                    >
                                        {{ msg.time }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Input Bar -->
                        <div class="p-8 shrink-0">
                            <form @submit.prevent="sendMessage" class="relative group">
                                <input 
                                    v-model="newMessage"
                                    placeholder="Type your message here..."
                                    class="w-full bg-white/5 border-none rounded-[2rem] py-5 pl-8 pr-32 text-white placeholder-zinc-500 focus:ring-2 focus:ring-admin-modern transition-all"
                                />
                                <div class="absolute right-3 top-2 flex items-center gap-2">
                                    <button type="button" class="p-3 text-zinc-500 hover:text-white transition-colors">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                    </button>
                                    <button type="submit" class="bg-admin-modern text-black px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs shadow-lg shadow-admin-modern/20 hover:scale-105 transition-all">
                                        Send
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div v-else class="flex-1 flex flex-col items-center justify-center p-20 text-center">
                        <div class="w-20 h-20 rounded-[2.5rem] bg-white/5 flex items-center justify-center text-zinc-700 mb-6 border border-white/5">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        </div>
                        <h3 class="text-xl font-black text-white uppercase tracking-tighter">No Conversation Selected</h3>
                        <p class="text-zinc-500 max-w-xs mt-2 font-medium">Select a customer from the left sidebar to start providing support.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
