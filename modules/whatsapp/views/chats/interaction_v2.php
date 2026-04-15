<?php defined('BASEPATH') || exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
  // CSRF
  $csrf = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
  $_SESSION['csrf_token'] = $csrf;

  // Data from PHP
  $numbers   = $numbers;      // [{id, phone, whatsapp_business_account_id, profile_picture_url}, …]
  $statuses  = $statuses;     // [{id, name}, …]
  $staff     = $staffs;       // [{staffid, firstname, lastname}, …]
  $quick     = $quick_replies;
  $templates = get_whatsapp_template();
?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@2.3.6/dist/purify.min.js"></script>
<div id="wrapper" class="h-90vh flex flex-col font-sans">
  <div class="content">
    <div id="app">
                <div 
  x-data="whatsappApp()" 
  x-init="init()" 
  class=" h-screen flex flex-col bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100"
>
  <!-- Top Bar: Dark Mode & Brand -->
  <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
    <h1 class="font-bold">WhatsApp Cloud Chat</h1>
    <button @click="dark = !dark" class="focus:outline-none">
      <template x-if="!dark"><i class="fa fa-moon"></i></template>
      <template x-if="dark"><i class="fa fa-sun"></i></template>
    </button>
  </div>

  <div class="flex flex-1 overflow-hidden">
    <!-- Sidebar -->
    <aside 
      :class="dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'"
      class="w-80 flex-shrink-0 border-r transition-all"
    >
      <!-- Profile -->
      <div class="p-4 flex items-center border-b dark:border-gray-700">
        <img :src="profilePic" class="h-10 w-10 rounded-full" alt="">
        <div class="ml-3 flex-1">
          <h2 class="font-semibold" x-text="profileName"></h2>
          <button @click="openNew()" class="text-sm text-blue-500">+ New Chat</button>
        </div>
      </div>

      <!-- Filters -->
      <div class="p-4 space-y-2">
        <input
          x-model="searchText" @input="debounceFetch()"
          placeholder="🔍 Search"
          class="w-full px-3 py-2 rounded border dark:border-gray-700 dark:bg-gray-900"
        >
        <template x-for="(opts, key) in filterOptions" :key="key">
          <select
            x-model="filter[key]" @change="fetchChats()"
            class="w-full px-2 py-2 rounded border dark:border-gray-700 dark:bg-gray-900"
          >
            <option :value="op.value" x-text="op.label" 
              x-for="op in opts" :key="op.value"></option>
          </select>
        </template>
      </div>
<select x-model="filter.number" @change="fetchChats()">…</select>
<select x-model="filter.type"   @change="fetchChats()">…</select>
<select x-model="filter.status" @change="fetchChats()">…</select>
<select x-model="filter.assigned"@change="fetchChats()">…</select>
<select x-model="filter.unread"  @change="fetchChats()">…</select>

      <!-- Chats -->
      <div class="flex-1 overflow-y-auto">
        <template x-if="loading">
          <div class="p-4 space-y-2">
            <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded animate-pulse"></div>
            <div class="h-4 w-3/4 bg-gray-300 dark:bg-gray-700 rounded animate-pulse"></div>
          </div>
        </template>
        <template x-for="c in chats" :key="c.id">
          <div 
            @click="selectChat(c.id)"
            :class="{'bg-blue-100': c.id===selectedChatId, 'hover:bg-gray-100 dark:hover:bg-gray-700': true}"
            class="p-3 flex items-start space-x-3 cursor-pointer border-b dark:border-gray-700"
          >
            <div class="h-10 w-10 bg-blue-500 text-white flex items-center justify-center rounded-full">
              <span x-text="c.name.split(' ').map(w=>w[0]).join('')"></span>
            </div>
            <div class="flex-1">
              <div class="flex justify-between">
                <h3 class="font-medium" x-text="c.name"></h3>
                <time class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(c.last_time)"></time>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 truncate" x-text="c.last_message"></p>
            </div>
            <div>
              <span 
                x-show="c.unread>0"
                class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"
                x-text="c.unread"
              ></span>
            </div>
          </div>
        </template>
      </div>
    </aside>

    <!-- Chat Window -->
    <div class="flex-1 flex flex-col">
      <!-- Header -->
      <div class="p-4 flex items-center justify-between border-b dark:border-gray-700">
        <button @click="toggleSidebar()" class="md:hidden">
          <i class="fa fa-bars"></i>
        </button>
        <div class="flex items-center space-x-3">
          <div class="h-10 w-10 bg-blue-500 text-white flex items-center justify-center rounded-full">
            <span x-text="selectedChat?.name?.split(' ').map(w=>w[0]).join('')"></span>
          </div>
          <div>
            <h3 class="font-semibold" x-text="selectedChat?.name"></h3>
            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="selectedChat?.phone"></p>
          </div>
        </div>
        <div class="space-x-2">
          <button @click="showTemplates = !showTemplates"><i class="fa fa-file-alt"></i></button>
          <button @click="generateAIResponse()"><i class="fa fa-robot"></i></button>
        </div>
      </div>

      <!-- Messages -->
      <div 
        x-ref="msgContainer"
        class="flex-1 p-4 overflow-y-auto bg-gray-50 dark:bg-gray-900 relative"
      >
        <!-- Jump to bottom -->
        <button 
          x-show="showJump"
          @click="$refs.msgContainer.scrollTop = $refs.msgContainer.scrollHeight"
          class="absolute bottom-4 right-4 bg-blue-500 text-white p-2 rounded-full shadow-lg"
        ><i class="fa fa-chevron-down"></i></button>

        <template x-for="(m,i) in messages" :key="m.id">
          <div class="mb-4">
            <template x-if="i===0 || new Date(messages[i].time).toDateString()!==new Date(messages[i-1].time).toDateString()">
              <div class="text-center text-gray-500 dark:text-gray-400 text-xs mb-2" x-text="formatDate(m.time)"></div>
            </template>
            <div :class="m.sent?'justify-end':'justify-start'" class="flex">
              <div 
                :class="m.sent?'bg-blue-200 text-right':'bg-white dark:bg-gray-800 text-left'" 
                class="max-w-xs p-3 rounded-lg shadow"
              >
                <p class="whitespace-pre-wrap" x-html="m.text"></p>
                <div class="mt-1 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                  <time x-text="formatTime(m.time)"></time>
                  <template x-if="m.sent">
                    <i :class="m.status==='read'?'fa fa-check-double text-blue-500':'fa fa-check'"></i>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Input -->
      <div class="p-4 bg-white dark:bg-gray-800 border-t dark:border-gray-700 flex items-center space-x-2">
        <!-- Drag-drop stub -->
        <div 
          @dragover.prevent 
          @drop.prevent="handleDrop"
          class="p-2 border-dashed border rounded text-gray-500 dark:text-gray-400 cursor-pointer"
        >Drop ▶︎</div>
        <!-- Emoji + quick -->
        <div class="relative">
          <button @click="showEmoji = !showEmoji"><i class="fa fa-smile"></i></button>
          <div 
            x-show="showEmoji"
            class="absolute bottom-10 bg-white dark:bg-gray-800 border dark:border-gray-700 p-2 rounded shadow-lg max-h-48 overflow-auto"
          >
            <template x-for="e in emojis[selectedCategory]" :key="e">
              <button @click="newMessage+=e" class="p-1">{{e}}</button>
            </template>
            <!-- Category tabs omitted for brevity -->
          </div>
        </div>
        <input 
          type="text"
          x-model="newMessage"
          @input="checkQuick()"
          @keydown.enter.prevent="sendMessage()"
          placeholder="Type…"
          class="flex-1 px-4 py-2 rounded-full border dark:border-gray-700 dark:bg-gray-900 focus:outline-none"
        >
        <button @click="attachFile"><i class="fa fa-paperclip"></i></button>
        <button @click="sendMessage" class="text-blue-600"><i class="fa fa-paper-plane"></i></button>

        <!-- Quick‐reply -->
        <ul 
          x-show="showQuick" 
          class="absolute bottom-16 left-32 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded shadow-lg max-h-40 overflow-auto"
        >
          <template x-for="(r,i) in quickFiltered" :key="i">
            <li @click="newMessage=r.message; showQuick=false" class="px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
              {{r.message}}
            </li>
          </template>
        </ul>
      </div>
    </div>
  </div>

  <!-- New Chat Modal -->
  <div 
    x-show="showNew"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"
  >
    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow-lg w-96">
      <h2 class="font-semibold mb-4">Start New Chat</h2>
      <select x-model="newFrom" class="w-full mb-3 p-2 border dark:border-gray-700 rounded">
        <template x-for="n in numbers" :key="n.id">
          <option :value="n.id" x-text="n.phone"></option>
        </template>
      </select>
      <input x-model="newTo" placeholder="Recipient…" class="w-full mb-3 p-2 border dark:border-gray-700 rounded">
      <textarea x-model="newTpl" placeholder="Template…" class="w-full mb-4 p-2 border dark:border-gray-700 rounded"></textarea>
      <div class="flex justify-end space-x-2">
        <button @click="showNew=false" class="px-4 py-2 border rounded">Cancel</button>
        <button @click="sendNew" class="px-4 py-2 bg-green-500 text-white rounded">Send</button>
      </div>
    </div>
  </div>
</div>
    </div>

    </div>
</div>

<?php init_tail(); ?>
<script>
function whatsappApp(){
  return {
    // STATE
    dark: false,
    loading: false,
    searchText: '',
    filter: {number:'*',type:'*',status:'*',assigned:'*',unread:'*'},
    numbers: <?php echo json_encode($numbers); ?>,
    types: ['staff','leads','contacts','customers','general'],
    statuses: <?php echo json_encode($statuses); ?>,
    staffList: <?php echo json_encode($staffs); ?>,
    quickReplies: <?php echo json_encode($quick_replies); ?>,
    templates: <?php echo json_encode($templates); ?>,
    emojis:{}, emojiCategories:[], selectedCategory:'',
    interactions:[], chats:[], selectedChatId:null, selectedChat:null,
    messages:[], newMessage:'', showEmoji:false, showQuick:false, quickFiltered:[],
    showJump:false, showNew:false, newFrom:'', newTo:'', newTpl:'',
    profilePic:'<?php echo module_dir_url(WHATSAPP_MODULE,"assets/images/icon.png"); ?>',
    profileName:'All Chats', chatBg:'<?php echo module_dir_url(WHATSAPP_MODULE,"assets/images/bg.jpg"); ?>',
    fetchInterval:null, filterTimeout:null,

    init(){
      this.loadEmojis();
      this.fetchChats();
      this.fetchInterval = setInterval(()=>this.fetchChats(), <?php echo get_option('whatsapp_reload_interval')?:5; ?>*1000);
      this.$watch('searchText', ()=>this.debounceFetch());
    },
    cleanup(){ clearInterval(this.fetchInterval) },

    async fetchChats(){
      this.loading=true;
      try{
        const res = await axios.get('<?php echo admin_url("whatsapp/interactions"); ?>',{params:{
          wa_no_id:this.filter.number,
          interaction_type:this.filter.type,
          status_id:this.filter.status,
          assigned_staff_id:this.filter.assigned,
          status:this.filter.unread,
          searchtext:this.searchText,
          limit:<?php echo get_option('whatsapp_load_more_limit')?:20; ?>
        }});
        this.chats=res.data.interactions||[];
      }catch(e){console.error(e)}
      finally{this.loading=false}
    },
    debounceFetch(){ clearTimeout(this.filterTimeout); this.filterTimeout=setTimeout(()=>this.fetchChats(),300) },

    async selectChat(id){
      this.selectedChatId=id;
      this.selectedChat=this.chats.find(c=>c.id===id);
      await this.fetchMessages(id);
      this.$nextTick(()=>{
        this.$refs.msgContainer.scrollTop=this.$refs.msgContainer.scrollHeight;
      });
    },
    async fetchMessages(id){
      try{
        const {data}=await axios.get('<?php echo admin_url("whatsapp/get_interaction_messages"); ?>',{params:{interaction_id:id,limit:<?php echo get_option('whatsapp_load_more_limit')?:20; ?>}});
        this.messages=(data.messages?.messages||[]).map(m=>({
          id:m.message_id,text:DOMPurify.sanitize(m.message),time:m.time_sent,
          sent:m.nature==='sent',status:m.status
        }));
      }catch(e){console.error(e)}
    },

    sendMessage(){
      if(!this.newMessage.trim())return;
      const fd=new FormData();
      fd.append('csrf_token_name','<?php echo $csrf; ?>');
      fd.append('id',this.selectedChatId);
      fd.append('message',this.newMessage);
      axios.post('<?php echo admin_url("whatsapp/webhook/send_message"); ?>',fd)
        .then(()=>{this.newMessage='';this.fetchMessages(this.selectedChatId)})
        .catch(console.error);
    },

    handleDrop(e){
      /* stub for attachments */
      console.log('dropped', e.dataTransfer.files);
    },
    toggleEmoji(){this.showEmoji=!this.showEmoji},
    checkQuick(){ 
      if(this.newMessage.endsWith('/')){
        this.showQuick=true;
        const q=this.newMessage.slice(0,-1).toLowerCase();
        this.quickFiltered=this.quickReplies.filter(r=>r.message.toLowerCase().includes(q));
      } else this.showQuick=false;
    },

    loadEmojis(){
      fetch('<?php echo module_dir_url(WHATSAPP_MODULE,"assets/json/emojis.json"); ?>')
        .then(r=>r.json()).then(js=>{
          this.emojis=js;
          this.emojiCategories=Object.keys(js);
          this.selectedCategory=this.emojiCategories[0];
        });
    },

    formatTime(t){return new Date(t).toLocaleTimeString()},
    formatDate(t){return new Date(t).toLocaleDateString()},
    showDateSeparator(i){
      if(i===0)return true;
      return new Date(this.messages[i].time).toDateString()!==new Date(this.messages[i-1].time).toDateString();
    },

    toggleSidebar(){ this.$el.classList.toggle('dark'); /* dark mode */ },
    openNew(){this.showNew=true},
    sendNew(){
      const fd=new FormData();
      fd.append('csrf_token_name','<?php echo $csrf; ?>');
      fd.append('from_number_id',this.newFrom);
      fd.append('phone_number',this.newTo);
      fd.append('template_id',this.newTpl);
      axios.post('<?php echo admin_url("whatsapp/initiate_message"); ?>',fd)
        .then(()=>{this.showNew=false;this.fetchChats()})
        .catch(console.error);
    },
    // *** Revised AI helper, nothing to do with filters ***
    async generateAIResponse() {
      if (!this.selectedChatId) return;
      try {
        // Build query string
        const qs = new URLSearchParams({
          interaction_id: this.selectedChatId,
          message:        this.newMessage
        }).toString();

        const url = `${admin_url}whatsapp/get_aireply?${qs}`;
        const raw = await fetch(url);
        if (!raw.ok) throw new Error('AI request failed');

        const json = await raw.json();
        if (json.reply) {
          this.newMessage = json.reply;
        } else {
          console.warn('AI sent no reply');
        }
      } catch (err) {
        console.error('generateAIResponse error', err);
      }
    },

  }
}

window.addEventListener('beforeunload',()=>{ if(window.__x) window.__x.$data.cleanup() });
</script>
