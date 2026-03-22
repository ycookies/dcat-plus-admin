class WorkflowDesigner {
    constructor(el, options) {
        this.el = el;
        this.options = options;
        this.init();
    }

    init() {
        // 初始化Vue应用
        this.app = new Vue({
            el: this.el,
            data: {
                workflow: {
                    name: '',
                    nodes: [],
                    transitions: [],
                    config: {}
                }
            },
            mounted() {
                if (this.options.workflowId) {
                    this.loadWorkflow();
                }
            },
            methods: {
                loadWorkflow() {
                    // 加载工作流数据
                    $.ajax({
                        url: `${this.options.apiUrl.load}/${this.options.workflowId}`,
                        method: 'GET',
                        success: (response) => {
                            if (response.success) {
                                this.workflow = response.data;
                                this.initDesigner();
                            }
                        }
                    });
                },
                
                saveWorkflow() {
                    // 保存工作流数据
                    const url = this.options.workflowId 
                        ? `${this.options.apiUrl.save}/${this.options.workflowId}`
                        : this.options.apiUrl.save;
                        
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: this.workflow,
                        success: (response) => {
                            if (response.success) {
                                Dcat.success('保存成功');
                            }
                        }
                    });
                }
            }
        });
    }
} 