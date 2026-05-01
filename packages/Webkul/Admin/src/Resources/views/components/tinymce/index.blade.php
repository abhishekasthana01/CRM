@php($placeholders = app('\Webkul\Automation\Helpers\Entity')->getEmailTemplatePlaceholders())

<v-tinymce {{ $attributes }}></v-tinymce>

@pushOnce('scripts')
    <!--
        TODO (@devansh-webkul): Only this portion is pending; it just needs to be integrated using the Vite bundler. Currently,
        there is an issue with relative paths in the plugins. I intend to address this task at the end.
    -->
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.6.2/tinymce.min.js"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    ></script>

    <script
        type="text/x-template"
        id="v-tinymce-template"
    >
    </script>

    <script type="module">
        app.component('v-tinymce', {
            template: '#v-tinymce-template',

            props: ['selector', 'field'],

            data() {
                return {
                    currentSkin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',

                    currentContentCSS: document.documentElement.classList.contains('dark') ? 'dark' : 'default',

                    isLoading: false,
                };
            },

            mounted() {
                this.destroyTinymceInstance();

                this.init();

                this.$emitter.on('change-theme', (theme) => {
                    this.destroyTinymceInstance();

                    this.currentSkin = (theme === 'dark') ? 'oxide-dark' : 'oxide';
                    this.currentContentCSS = (theme === 'dark') ? 'dark' : 'default';

                    this.init();
                });
            },

            methods: {
                destroyTinymceInstance() {
                    if (! tinymce.activeEditor) {
                        return;
                    }

                    tinymce.activeEditor.destroy();
                },

                init() {
                    let self = this;

                    let tinyMCEHelper = {
                        initTinyMCE: function(extraConfiguration) {
                            let self2 = this;

                            let config = {
                                relative_urls: false,
                                menubar: false,
                                remove_script_host: false,
                                document_base_url: '{{ asset('/') }}',
                                uploadRoute: '{{ route('admin.tinymce.upload') }}',
                                csrfToken: '{{ csrf_token() }}',
                                ...extraConfiguration,
                                skin: self.currentSkin,
                                content_css: self.currentContentCSS,
                            };

                            const image_upload_handler = (blobInfo, progress) => new Promise((resolve, reject) => {
                                self2.uploadImageHandler(config, blobInfo, resolve, reject, progress);
                            });

                            tinymce.init({
                                ...config,

                                automatic_uploads: true,
                                paste_data_images: true,

                                file_picker_callback: function(cb, value, meta) {
                                    self2.filePickerCallback(config, cb, value, meta);
                                },

                                images_upload_handler: image_upload_handler,
                            });
                        },

                        filePickerCallback: function(config, cb, value, meta) {
                            let input = document.createElement('input');
                            input.setAttribute('type', 'file');

                            // Accept all file types for drag-and-drop / file picker
                            input.setAttribute('accept', '*/*');

                            input.onchange = function() {
                                let file = this.files[0];

                                if (! file) return;

                                // Upload the file to the server
                                let formData = new FormData();
                                formData.append('_token', config.csrfToken);
                                formData.append('file', file);

                                let xhr = new XMLHttpRequest();
                                xhr.open('POST', config.uploadRoute);
                                xhr.onload = function() {
                                    if (xhr.status >= 200 && xhr.status < 300) {
                                        let json = JSON.parse(xhr.responseText);

                                        if (json && json.location) {
                                            cb(json.location, { title: file.name });
                                        }
                                    }
                                };
                                xhr.send(formData);
                            };

                            input.click();
                        },

                        uploadImageHandler: function(config, blobInfo, resolve, reject, progress) {
                            let xhr, formData;

                            xhr = new XMLHttpRequest();

                            xhr.withCredentials = false;

                            xhr.open('POST', config.uploadRoute);

                            xhr.upload.onprogress = ((e) => progress((e.loaded / e.total) * 100));

                            xhr.onload = function() {
                                let json;

                                if (xhr.status === 403) {
                                    reject("@lang('admin::app.components.tiny-mce.http-error')", {
                                        remove: true
                                    });

                                    return;
                                }

                                if (xhr.status < 200 || xhr.status >= 300) {
                                    reject("@lang('admin::app.components.tiny-mce.http-error')");

                                    return;
                                }

                                json = JSON.parse(xhr.responseText);

                                if (! json || typeof json.location != 'string') {
                                    reject("@lang('admin::app.components.tiny-mce.invalid-json')" + xhr.responseText);

                                    return;
                                }

                                resolve(json.location);
                            };

                            xhr.onerror = (()=>reject("@lang('admin::app.components.tiny-mce.upload-failed')"));

                            formData = new FormData();
                            formData.append('_token', config.csrfToken);
                            formData.append('file', blobInfo.blob(), blobInfo.filename());

                            xhr.send(formData);
                        },
                    };

                    tinyMCEHelper.initTinyMCE({
                        selector: this.selector,
                        plugins: 'image media wordcount save fullscreen code table lists link',
                        toolbar: 'placeholders | bold italic strikethrough forecolor backcolor image alignleft aligncenter alignright alignjustify | link hr | numlist bullist outdent indent | removeformat | code | table',
                        image_advtab: true,
                        directionality: 'ltr',
                        setup: (editor) => {
                            let toggleState = false;

                            editor.ui.registry.addMenuButton('placeholders', {
                                text: 'Placeholders',
                                fetch: function (callback) {
                                    const items = [
                                        @foreach($placeholders as $placeholder)
                                            {
                                                type: 'nestedmenuitem',
                                                text: '{{ $placeholder['text'] }}',
                                                getSubmenuItems: () => [
                                                    @foreach($placeholder['menu'] as $child)
                                                        {
                                                            type: 'menuitem',
                                                            text: '{{ $child['text'] }}',
                                                            onAction: function () {
                                                                editor.insertContent('{{ $child['value'] }}');
                                                            },
                                                        },
                                                    @endforeach
                                                ],
                                            },
                                        @endforeach
                                    ];

                                    callback(items);
                                }
                            });

                            // Drag & drop file upload handler
                            editor.on('drop', (e) => {
                                let dataTransfer = e.dataTransfer;

                                if (! dataTransfer || ! dataTransfer.files || dataTransfer.files.length === 0) {
                                    return;
                                }

                                e.preventDefault();
                                e.stopPropagation();

                                Array.from(dataTransfer.files).forEach((file) => {
                                    let isImage = file.type.startsWith('image/');

                                    // For images, let TinyMCE's built-in handler take over
                                    if (isImage) {
                                        return;
                                    }

                                    // For non-image files (PDF, docs, etc.), upload and insert as link
                                    let formData = new FormData();
                                    formData.append('_token', '{{ csrf_token() }}');
                                    formData.append('file', file);

                                    let xhr = new XMLHttpRequest();
                                    xhr.open('POST', '{{ route('admin.tinymce.upload') }}');
                                    xhr.onload = function() {
                                        if (xhr.status >= 200 && xhr.status < 300) {
                                            let json = JSON.parse(xhr.responseText);

                                            if (json && json.location) {
                                                editor.insertContent(
                                                    `<a href="${json.location}" target="_blank" title="${file.name}">📎 ${file.name}</a>&nbsp;`
                                                );
                                            }
                                        }
                                    };
                                    xhr.send(formData);
                                });
                            });

                            // Visual drag-over feedback
                            editor.on('dragover', (e) => {
                                e.preventDefault();
                                editor.getBody().style.outline = '2px dashed #6366f1';
                                editor.getBody().style.outlineOffset = '-4px';
                            });

                            editor.on('dragleave', () => {
                                editor.getBody().style.outline = '';
                                editor.getBody().style.outlineOffset = '';
                            });

                            editor.on('drop', () => {
                                editor.getBody().style.outline = '';
                                editor.getBody().style.outlineOffset = '';
                            });

                            ['change', 'paste', 'keyup'].forEach((event) => {
                                editor.on(event, () => this.field.onInput(editor.getContent()));
                            });
                        }
                    });
                },
            },
        })
    </script>
@endPushOnce
