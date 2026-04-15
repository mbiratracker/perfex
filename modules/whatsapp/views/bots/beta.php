<?php
// Example: fetch all approved templates from DB (if you use them)
$allTemplates = get_whatsapp_template(); 
// Convert to JSON for embedding in the page:
$templatesJson = json_encode($allTemplates);

// If you have an existing flow record:
$flowData = $flow['flow_data'] ?? '{}';
?><!DOCTYPE html>
<html lang="en" x-data="flowBuilder()" x-init="init()" :class="darkMode ? 'dark-theme' : ''">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Alpine.js WhatsApp Flow Builder</title>

  <!-- Tailwind & custom styles -->
  <link rel="stylesheet" href="<?= htmlspecialchars(module_dir_url('whatsapp','assets/css/twailwind.css')) ?>" />
  <style>
    #builder { background-image: linear-gradient(to right, #ccc 1px, transparent 1px), linear-gradient(to bottom, #ccc 1px, transparent 1px); background-size:20px 20px; }
    body, .node { user-select:none; }
    .delete-node { position:absolute; top:-6px; right:-6px; background:red; color:#fff; width:20px; height:20px; border-radius:50%; text-align:center; line-height:20px; cursor:pointer; }
    .output-point, .input-point, .branch-output { width:12px; height:12px; border-radius:50%; position:absolute; cursor:crosshair; }
    .output-point { background:green; right:0; top:50%; transform:translateY(-50%); }
    .input-point  { background:blue; left:0; top:50%; transform:translateY(-50%); }
    .branch-output { background:purple; right:0; }
    .action-bar { position:fixed; bottom:1rem; right:1rem; display:flex; gap:0.5rem; z-index:50; }
  </style>
</head>
<body class="bg-gray-100" @mouseup="endDrag" @mousemove="onMouseMove($event)" @keydown.window="handleKeydown($event)" tabindex="0">

  <!-- Sidebar -->
  <div class="w-64 bg-white p-4 shadow-lg fixed left-0 h-screen overflow-auto">
    <h3 class="font-semibold mb-4">WhatsApp Nodes</h3>
    <template x-for="comp in components" :key="comp.type">
      <div class="component p-2 mb-2 rounded cursor-pointer text-white flex items-center" :class="comp.bg" @mousedown.prevent="startDrag(comp.type)">
        <span class="mr-2" x-text="comp.icon"></span><span x-text="comp.label"></span>
      </div>
    </template>
    <button class="mt-6 px-3 py-2 rounded bg-gray-600 text-white w-full" @click="darkMode=!darkMode">Toggle Theme</button>
  </div>

  <!-- Builder Area -->
  <div id="builder" class="ml-64 h-screen relative overflow-hidden">
    <svg class="absolute inset-0 w-full h-full pointer-events-none">
      <template x-for="conn in connections" :key="conn.id">
        <path :d="conn.d" :stroke="conn.branch===0?'red':'green'" fill="none" stroke-width="2"></path>
      </template>
    </svg>
    <template x-for="node in nodes" :key="node.id">
      <div class="node bg-white shadow-lg p-4 border absolute" 
           :class="selectedNodes.has(node.id) ? 'ring-2 ring-purple-500' : ''" 
           :style="`left:${node.position.x}px;top:${node.position.y}px`" 
           @mousedown.stop.prevent="startNodeDrag(node,$event)" 
           @dblclick.stop.prevent="openModal(node)" 
           @click.stop.prevent="toggleSelect(node,$event)">
        <div class="delete-node" @click.stop.prevent="removeNode(node)">&times;</div>
        <strong class="uppercase text-blue-600" x-text="node.type"></strong>
        <div class="mt-2 text-sm text-gray-600" x-html="renderNodeContent(node)"></div>
        <!-- Output/Input Points -->
        <template x-if="node.type!=='condition'">
          <div class="output-point" @mousedown.stop.prevent="startConnect(node,null)"></div>
        </template>
        <template x-if="node.type==='condition'">
          <div class="branch-output" :style="`right:0;top:25%`" @mousedown.stop.prevent="startConnect(node,0)" title="${node.data.branches[0].label}"></div>
          <div class="branch-output" :style="`right:0;top:75%`" @mousedown.stop.prevent="startConnect(node,1)" title="${node.data.branches[1].label}"></div>
        </template>
        <div class="input-point" @mouseup.stop.prevent="endConnect(node)"></div>
      </div>
    </template>
  </div>

  <!-- Edit Modal -->
  <div x-show="showModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded shadow-lg w-96 max-h-[90vh] overflow-auto" @click.away="closeModal">
      <h3 class="text-lg font-semibold mb-4">Edit <span x-text="activeNode.type"></span> Node</h3>

      <!-- Condition Node -->
      <template x-if="activeNode && activeNode.type==='condition'">
        <div class="mb-4">
          <label class="block mb-1">Condition Expression (JS):</label>
          <textarea x-model="activeNode.data.conditionsExpr" class="w-full p-2 border rounded" rows="2"></textarea>
        </div>
        <div class="mb-4">
          <label class="block mb-1">True Branch Label:</label>
          <input x-model="activeNode.data.branches[0].label" class="w-full p-2 border rounded" />
        </div>
        <div class="mb-4">
          <label class="block mb-1">False Branch Label:</label>
          <input x-model="activeNode.data.branches[1].label" class="w-full p-2 border rounded" />
        </div>
      </template>

      <!-- Other Node Types (Text, Image, etc.) -->
      <template x-if="activeNode && activeNode.type==='text'">
        <div><label>Text:</label><textarea x-model="activeNode.data.text" class="w-full p-2 border rounded" rows="3"></textarea></div>
      </template>
      <template x-if="activeNode && activeNode.type==='image'">
        <div>
          <label>URL:</label><input x-model="activeNode.data.url" class="w-full p-2 border rounded" />
          <label class="mt-2">Caption:</label><input x-model="activeNode.data.caption" class="w-full p-2 border rounded" />
        </div>
      </template>
      <template x-if="activeNode && (activeNode.type==='audio'||activeNode.type==='video')">
        <div><label>URL:</label><input x-model="activeNode.data.url" class="w-full p-2 border rounded" /></div>
      </template>
      <template x-if="activeNode && activeNode.type==='template'">
        <div>
          <label>Template:</label>
          <select x-model="activeNode.data.template_id" class="w-full p-2 border rounded">
            <option value="">-- Select --</option>
            <template x-for="tpl in ALL_TEMPLATES" :key="tpl.id">
              <option :value="tpl.id" x-text="tpl.template_name"></option>
            </template>
          </select>
        </div>
      </template>
      <template x-if="activeNode && activeNode.type==='delay'">
        <div><label>Delay (ms):</label><input type="number" x-model="activeNode.data.delay" class="w-full p-2 border rounded" /></div>
      </template>
      <template x-if="activeNode && activeNode.type==='start'">
        <div>
          <label>Command:</label><input x-model="activeNode.data.triggerCommand" class="w-full p-2 border rounded" />
          <label class="mt-2">Type:</label><input x-model="activeNode.data.triggerType" class="w-full p-2 border rounded" />
        </div>
      </template>
      <template x-if="activeNode && activeNode.type==='end'">
        <div><label>Message:</label><textarea x-model="activeNode.data.message" class="w-full p-2 border rounded" rows="2"></textarea></div>
      </template>
      <template x-if="activeNode && activeNode.type==='cta'">
        <div class="space-y-2">
          <template x-for="(btn,idx) in activeNode.data.buttons" :key="idx">
            <div class="flex space-x-2">
              <input x-model="btn.id" placeholder="ID" class="w-1/4 p-2 border rounded" />
              <input x-model="btn.title" placeholder="Title" class="w-1/3 p-2 border rounded" />
              <input x-model="btn.url" placeholder="URL" class="flex-1 p-2 border rounded" />
              <button @click="activeNode.data.buttons.splice(idx,1)" class="text-red-500">&times;</button>
            </div>
          </template>
          <button @click="activeNode.data.buttons.push({id:'',title:'',url:''})" class="mt-2 px-3 py-1 bg-green-500 text-white rounded">+ Add Button</button>
        </div>
      </template>

      <div class="flex justify-end mt-4">
        <button @click="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">Cancel</button>
        <button @click="saveNode()" class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
      </div>
    </div>
  </div>

  <!-- Action Bar -->
  <div class="action-bar">
    <button @click="undo()" class="bg-gray-500 text-white px-3 py-2 rounded">Undo</button>
    <button @click="redo()" class="bg-gray-500 text-white px-3 py-2 rounded">Redo</button>
    <button @click="showFlowJson()" class="bg-yellow-500 text-white px-3 py-2 rounded">Show JSON</button>
    <button @click="importExportModal=true; flowJson=''" class="bg-indigo-500 text-white px-3 py-2 rounded">Import JSON</button>
    <button @click="saveFlow()" class="bg-green-600 text-white px-3 py-2 rounded">Save Flow</button>
  </div>

  <!-- Hidden Form -->
  <?php echo form_open(admin_url('whatsapp/bots/updateflow'), ['id'=>'workflow-form']); ?>
    <input type="hidden" id="flow_data" name="flow_data" value="" />
  <?php echo form_close(); ?>

  <!-- Alpine.js Script -->
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script>
    function flowBuilder() {
      return {
        darkMode: false,
        importExportModal: false,
        flowJson: '',
        undoStack: [], redoStack: [],
        selectedNodes: new Set(),
        connectStart: null,
        ALL_TEMPLATES: <?= $templatesJson ?>,
        components: [
          { type:'start',    label:'Start',       icon:'🚀', bg:'bg-green-700' },
          { type:'text',     label:'Text',        icon:'📩', bg:'bg-blue-500'  },
          { type:'image',    label:'Image',       icon:'🖼', bg:'bg-blue-500'  },
          { type:'audio',    label:'Audio',      icon:'🎵', bg:'bg-blue-500'  },
          { type:'video',    label:'Video',      icon:'🎥', bg:'bg-blue-500'  },
          { type:'template', label:'Template',   icon:'📝', bg:'bg-orange-500'},
          { type:'delay',    label:'Delay',       icon:'⏱', bg:'bg-green-500'},
          { type:'condition',label:'Condition',   icon:'❓', bg:'bg-indigo-500'},
          { type:'cta',      label:'CTA',         icon:'👉', bg:'bg-purple-600'},
          { type:'end',      label:'End',        icon:'🏁', bg:'bg-red-700'   }
        ],
        nodes: [],
        connections: [],
        dragType: null,
        dragNode: null,
        draggingNode: false,
        dragOffset: { x:0, y:0 },
        showModal: false,
        activeNode: null,

        init() {
          this.pushState();
          this.$el.focus();
        },
        /* State Management */
        pushState() {
          this.undoStack.push(JSON.stringify({nodes:this.nodes,connections:this.connections}));
          if (this.undoStack.length>50) this.undoStack.shift();
          this.redoStack = [];
        },
        undo() {
          if (this.undoStack.length>1) {
            this.redoStack.push(this.undoStack.pop());
            this.loadState(JSON.parse(this.undoStack[this.undoStack.length-1]));
          }
        },
        redo() {
          if (this.redoStack.length) {
            this.undoStack.push(this.redoStack.pop());
            this.loadState(JSON.parse(this.undoStack[this.undoStack.length-1]));
          }
        },
        loadState(s) {
          this.nodes = JSON.parse(JSON.stringify(s.nodes));
          this.connections = JSON.parse(JSON.stringify(s.connections));
          this.updateConnections();
        },
        /* Node Drag & Drop */
        startDrag(type) { this.dragType = type; },
        onMouseMove(e) {
          if (this.draggingNode) {
            this.dragNode.position.x = Math.round((e.clientX - this.dragOffset.x)/20)*20;
            this.dragNode.position.y = Math.round((e.clientY - this.dragOffset.y)/20)*20;
            this.updateConnections();
          }
        },
        endDrag(e) {
          if (this.dragType) {
            const rect = this.$root.querySelector('#builder').getBoundingClientRect();
            const x = Math.round((e.clientX - rect.left)/20)*20;
            const y = Math.round((e.clientY - rect.top)/20)*20;
            this.addNode(this.dragType, { x, y });
          }
          this.dragType = null;
          this.draggingNode = false;
          this.dragNode = null;
        },
        addNode(type,pos) {
          let data = {};
          switch(type) {
            case 'start':    data={triggerCommand:'',triggerType:'',message:''}; break;
            case 'text':     data={text:''}; break;
            case 'image':    data={url:'',caption:''}; break;
            case 'audio':    case 'video': data={url:''}; break;
            case 'template': data={template_id:null}; break;
            case 'delay':    data={delay:1000}; break;
            case 'condition':data={conditionsExpr:'',branches:[{label:'True'},{label:'False'}]}; break;
            case 'cta':      data={buttons:[]}; break;
            case 'end':      data={message:''}; break;
          }
          this.nodes.push({ id:`n_${Date.now()}`, type, position:{...pos}, data });
          this.pushState();
        },
        startNodeDrag(node,e) {
          this.draggingNode = true;
          this.dragNode = node;
          this.dragOffset.x = e.clientX - node.position.x;
          this.dragOffset.y = e.clientY - node.position.y;
        },
        removeNode(node) {
          this.nodes = this.nodes.filter(n=>n.id!==node.id);
          this.connections = this.connections.filter(c=>c.from!==node.id && c.to!==node.id);
          this.pushState();
        },
        toggleSelect(node,e) {
          if (e.shiftKey) {
            this.selectedNodes.has(node.id) ? this.selectedNodes.delete(node.id) : this.selectedNodes.add(node.id);
          } else {
            this.selectedNodes.clear();
            this.selectedNodes.add(node.id);
          }
        },
        /* Connections */
        startConnect(node,branch) { this.connectStart={node,branch}; },
        endConnect(node) {
          if (this.connectStart && this.connectStart.node.id!==node.id) {
            const {node:sn,branch} = this.connectStart;
            const f = this.nodeCenter(sn), t = this.nodeCenter(node);
            const d = `M${f.x},${f.y} C${f.x+100},${f.y} ${t.x-100},${t.y} ${t.x},${t.y}`;
            this.connections.push({ id:`${sn.id}_${node.id}_${branch}`, from:sn.id, to:node.id, branch, d });
            this.pushState();
          }
          this.connectStart = null;
        },
        nodeCenter(n) { return { x:n.position.x+60, y:n.position.y+30 }; },
        updateConnections() {
          this.connections.forEach(c=>{
            const f = this.nodes.find(n=>n.id===c.from);
            const t = this.nodes.find(n=>n.id===c.to);
            if (f && t) {
              const fc = this.nodeCenter(f), tc = this.nodeCenter(t);
              c.d = `M${fc.x},${fc.y} C${fc.x+100},${fc.y} ${tc.x-100},${tc.y} ${tc.x},${tc.y}`;
            }
          });
        },
        /* Modal */
        openModal(node) { this.activeNode=node; this.showModal=true; },
        closeModal() { this.showModal=false; this.activeNode=null; },
        saveNode() { this.closeModal(); this.pushState(); },
        /* Render content */
        renderNodeContent(node) {
          switch(node.type) {
            case 'start': return `Cmd: ${node.data.triggerCommand}`;
            case 'text':  return node.data.text;
            case 'image': return `<img src="${node.data.url}" class="max-w-full h-24 object-cover" /><p>${node.data.caption}</p>`;
            case 'audio': return `<audio controls src="${node.data.url}"></audio>`;
            case 'video': return `<video controls src="${node.data.url}" class="max-w-full h-24"></video>`;
            case 'template': return `Template: ${node.data.template_id}`;
            case 'delay': return `Delay: ${node.data.delay} ms`;
            case 'condition': return `Expr: ${node.data.conditionsExpr}`;
            case 'cta': return `Buttons: ${node.data.buttons.map(b=>b.title).join(', ')}`;
            case 'end': return node.data.message;
            default: return '';
          }
        },
        /* Keyboard */
        handleKeydown(e) {
          if (e.ctrlKey && e.key==='z') { e.preventDefault(); this.undo(); }
          if (e.ctrlKey && e.key==='y') { e.preventDefault(); this.redo(); }
          if (e.key==='Delete') {
            this.selectedNodes.forEach(id=>{
              const node = this.nodes.find(n=>n.id===id);
              if (node) this.removeNode(node);
            });
          }
          if (['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.key)) {
            const step=20;
            this.selectedNodes.forEach(id=>{
              const n=this.nodes.find(x=>x.id===id);
              if (n) {
                if(e.key==='ArrowUp') n.position.y-=step;
                if(e.key==='ArrowDown') n.position.y+=step;
                if(e.key==='ArrowLeft') n.position.x-=step;
                if(e.key==='ArrowRight') n.position.x+=step;
              }
            });
            this.updateConnections();
            this.pushState();
          }
        },
        /* JSON Import/Export */
        getCurrentFlowState() { return { nodes:this.nodes, connections:this.connections }; },
        showFlowJson() { this.flowJson=JSON.stringify(this.getCurrentFlowState(),null,2); this.importExportModal=true; },
        importFlow() { try { const f=JSON.parse(this.flowJson); this.loadFlow(f); this.importExportModal=false; } catch { alert('Invalid JSON'); } },
        loadFlow(f) {
          this.nodes=f.nodes; this.connections=f.connections;
          this.updateConnections(); this.pushState();
        },
        /* Validation & Save */
        saveFlow() {
          const flow=this.getCurrentFlowState(); let errs=[];
          flow.nodes.forEach(n=>{
            if (n.type==='condition') {
              const outs=flow.connections.filter(c=>c.from===n.id);
              if (outs.length!==2) errs.push(`Condition ${n.id} needs 2 branches`);
            }
          });
          if (errs.length) { alert('Errors:\n'+errs.join('\n')); return; }
          document.querySelector('#flow_data').value=JSON.stringify(flow);
          document.querySelector('#workflow-form').submit();
        }
      }
    }
  </script>
</body>
</html>