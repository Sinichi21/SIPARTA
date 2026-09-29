import {
    ClassicEditor,
    Essentials,
    Paragraph,
    Bold,
    Italic,
    Underline,
    Strikethrough,
    Heading,
    Link,
    List,
    Alignment,
    BlockQuote,
    Table,
    TableToolbar,
    Undo,
} from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';

const instances = new WeakMap();

async function mount(element, initialData, onChange) {
    if (!element || instances.has(element)) {
        return;
    }

    const editor = await ClassicEditor.create(element, {
        licenseKey: import.meta.env.VITE_CKEDITOR_LICENSE_KEY || 'GPL',
        plugins: [
            Essentials,
            Paragraph,
            Bold,
            Italic,
            Underline,
            Strikethrough,
            Heading,
            Link,
            List,
            Alignment,
            BlockQuote,
            Table,
            TableToolbar,
            Undo,
        ],
        toolbar: [
            'undo',
            'redo',
            '|',
            'heading',
            '|',
            'bold',
            'italic',
            'underline',
            'strikethrough',
            '|',
            'alignment',
            '|',
            'bulletedList',
            'numberedList',
            '|',
            'link',
            'insertTable',
            'blockQuote',
        ],
        table: {
            contentToolbar: [
                'tableColumn',
                'tableRow',
                'mergeTableCells',
            ],
        },
        initialData: initialData || '',
    });

    editor.model.document.on('change:data', () => {
        onChange(editor.getData());
    });

    instances.set(element, editor);
}

async function destroy(element) {
    const editor = instances.get(element);

    if (!editor) {
        return;
    }

    instances.delete(element);

    try {
        await editor.destroy();
    } catch (_) {
        //
    }
}

window.SipartaCkeditor = {
    mount,
    destroy,
};
