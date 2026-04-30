# DolphinUploaderCore 纯逻辑文件上传组件

一个功能强大、高性能的纯逻辑文件上传组件，专为现代 Web 应用设计。不包含任何 UI 组件，完全通过事件系统与外部 UI 交互，提供最大的灵活性和可定制性。

## ✨ 核心特性

### 🚀 高性能上传
- **分片上传**：支持大文件分片上传，可配置分片大小
- **断点续传**：网络中断后可自动恢复上传进度
- **并发控制**：可配置同时上传的文件数量，避免资源占用过多
- **秒传检测**：基于文件哈希的智能秒传，节省带宽和时间

### 🔐 安全可靠
- **文件验证**：支持文件类型、大小、内容验证
- **哈希计算**：支持 SHA1 文件哈希，确保文件完整性
- **错误处理**：完善的错误处理和重试机制
- **内存优化**：大文件流式处理，避免内存溢出

### 🎛️ 高度可配置
- **预设配置**：提供 7 种常用场景的预设配置
- **灵活接口**：支持自定义上传接口和参数
- **多种策略**：支持快速、准确、混合哈希策略
- **事件驱动**：17 种事件类型，完整覆盖上传生命周期

### 🎨 纯逻辑设计
- **无UI依赖**：不包含任何UI组件，完全通过事件交互
- **框架无关**：可与任何前端框架或原生 JavaScript 集成
- **轻量级**：核心逻辑精简，体积小巧
- **扩展性强**：基于事件的架构便于功能扩展

## 📦 安装使用

### 直接引入
```html
<script src="path/to/uploader-core.js"></script>
```

### ES6 模块
```javascript
import { DolphinUploaderCore, UPLOADER_EVENTS } from './uploader-core.js';
```

### CommonJS
```javascript
const { DolphinUploaderCore, UPLOADER_EVENTS } = require('./uploader-core.js');
```

## 🎯 快速开始

### 基础用法

```javascript
// 创建上传器实例
const uploader = new DolphinUploaderCore({
    maxFileSize: '10MB',
    allowedTypes: ['image/*', 'application/pdf'],
    multiple: true,
    autoUpload: true,
    
    endpoints: {
        upload: '/admin/api/upload',
        check: '/admin/api/checkFile'
    },
    
    // 事件回调
    onUploaderFileAdded: (event, data) => {
        console.log('文件添加:', data.fileItem.name);
        // 更新UI显示新增文件
        updateFileList(data.fileItem);
    },
    
    onUploaderUploadProgress: (event, data) => {
        console.log('上传进度:', data.progress + '%');
        // 更新进度条
        updateProgress(data.file.id, data.progress);
    },
    
    onUploaderUploadSuccess: (event, data) => {
        console.log('上传成功:', data.file.name);
        // 处理上传成功
        handleUploadSuccess(data.file, data.response);
    }
});

// 添加文件
const input = document.querySelector('input[type="file"]');
input.addEventListener('change', async (e) => {
    if (e.target.files.length > 0) {
        await uploader.addFiles(e.target.files);
    }
});
```

### 使用预设配置

```javascript
// 头像上传：单文件，2MB限制，仅图片
const avatarUploader = new DolphinUploaderCore({
    preset: 'avatar',
    onUploaderUploadSuccess: (event, data) => {
        document.getElementById('avatar').src = data.response.url;
    }
});

// 文档上传：多文件，支持大文件分片上传
const docUploader = new DolphinUploaderCore({
    preset: 'documents',
    endpoints: {
        upload: '/api/upload/document',
        chunk: '/api/upload/chunk',
        merge: '/api/upload/merge'
    }
});

// 快速上传：无限制，最快速度
const fastUploader = new DolphinUploaderCore({
    preset: 'fast',
    concurrent: 0, // 无限并发
    retries: 1     // 减少重试
});
```

## ⚙️ 配置选项

### 基础配置

| 选项 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `maxFileSize` | `string\|number` | `'10MB'` | 最大文件大小，支持字符串格式（如'10MB'）或字节数 |
| `allowedTypes` | `string[]` | `['image/*']` | 允许的文件类型，支持MIME类型和文件扩展名 |
| `multiple` | `boolean` | `true` | 是否支持多文件选择 |
| `autoUpload` | `boolean` | `true` | 是否在添加文件后自动开始上传 |
| `maxFiles` | `number` | `undefined` | 最大文件数量限制 |

### 分片上传配置

| 选项 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `chunked` | `boolean` | `false` | 是否启用分片上传 |
| `chunkSize` | `string\|number` | `'1MB'` | 分片大小 |
| `resumable` | `boolean` | `true` | 是否支持断点续传 |

### 并发控制配置

| 选项 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `concurrent` | `number` | `3` | 同时上传的文件数，0 表示无限制 |
| `retries` | `number` | `3` | 失败重试次数 |
| `retryDelay` | `number` | `1000` | 重试间隔时间（毫秒） |

### 接口配置

```javascript
{
    endpoints: {
        upload: '/admin/api/upload',        // 直接上传接口
        check: '/admin/api/checkFile',      // 文件检查接口（秒传）
        save: '/admin/api/saveFile',        // 保存文件接口
        chunk: '/admin/api/uploadChunk',    // 分片上传接口
        merge: '/admin/api/mergeChunks',    // 分片合并接口
        getChunks: '/admin/api/getUploadedChunks' // 获取已上传分片接口
    }
}
```

### 预设配置

| 预设名称 | 适用场景 | 主要特点 |
|---------|----------|----------|
| `avatar` | 头像上传 | 单文件，2MB限制，仅图片格式 |
| `images` | 图片上传 | 多图片，10MB限制，支持预览 |
| `documents` | 文档上传 | 大文档，50MB限制，分片上传 |
| `videos` | 视频上传 | 大视频，无限制，大分片 |
| `fast` | 快速上传 | 无限制，最快速度，无验证 |
| `secure` | 安全上传 | 严格验证，内容检查，XSS防护 |
| `simple` | 简单上传 | 极简配置，快速原型开发 |

### 高级配置

| 选项 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `checkStrategy` | `string` | `'accurate'` | 哈希检查策略：`fast`、`accurate`、`hybrid` |
| `extraData` | `object\|function` | `null` | 额外上传参数，可以是对象或返回对象的函数 |
| `fileFieldLast` | `boolean` | `false` | 是否将文件字段放在表单数据末尾 |
| `abortOnExtraDataError` | `boolean` | `true` | extraData 函数失败时是否中止上传 |
| `debug` | `boolean` | `false` | 是否启用调试模式 |

## 📡 API 方法

### 文件管理

```javascript
// 添加文件
await uploader.addFiles(files);                    // 添加文件或文件数组
await uploader.addFiles(fileList);                 // 添加FileList对象
await uploader.addFiles([file1, file2]);           // 添加文件数组

// 获取文件信息
const allFiles = uploader.getFiles();              // 获取所有文件
const someFiles = uploader.getFiles(['id1', 'id2']); // 获取指定文件
const stats = uploader.getStats();                 // 获取统计信息
```

### 上传控制

```javascript
// 开始上传
await uploader.uploadFiles();                      // 上传所有待上传文件
await uploader.uploadFiles(fileIds);               // 上传指定文件
await uploader.uploadFiles([fileItem1, fileItem2]); // 上传指定文件项

// 暂停上传
uploader.pauseFiles();                             // 暂停所有上传
uploader.pauseFiles(fileIds);                      // 暂停指定文件

// 恢复上传
uploader.resumeFiles();                            // 恢复所有暂停的上传
uploader.resumeFiles(fileIds);                     // 恢复指定文件

// 取消上传
uploader.cancelFiles();                            // 取消所有上传
uploader.cancelFiles(fileIds);                     // 取消指定文件

// 重试上传
uploader.retryFiles();                             // 重试所有失败的文件
uploader.retryFiles(fileIds);                      // 重试指定文件
```

### 文件操作

```javascript
// 移除文件
uploader.removeFiles(fileIds);                     // 移除指定文件
uploader.clearFiles();                             // 清空所有文件

// 销毁实例
uploader.destroy();                                // 销毁上传器，清理所有资源
```

## 🎪 事件系统

### 事件列表

| 事件名称 | 触发时机 | 数据参数 |
|---------|----------|----------|
| `INIT` | 上传器初始化完成 | `{ config, initTime, timestamp }` |
| `FILE_ADDED` | 单个文件添加 | `{ fileItem, file }` |
| `FILES_ADDED` | 批量文件添加完成 | `{ files, fileItems, total, errors }` |
| `BEFORE_BATCH_UPLOAD` | 批量上传开始前 | `{ files, fileItems, total }` |
| `BATCH_UPLOAD_START` | 批量上传开始 | `{ files, fileItems, total }` |
| `BEFORE_UPLOAD` | 单个文件上传前 | `{ file, fileItem }` |
| `PREPARE_UPLOAD` | 哈希计算后、上传前 | `{ file, fileItem }` |
| `UPLOAD_START` | 开始上传 | `{ file, fileItem }` |
| `BEFORE_ACTUAL_UPLOAD` | 实际上传开始前 | `{ file, fileItem }` |
| `UPLOAD_PROGRESS` | 上传进度更新 | `{ file, fileItem, loaded, total, progress }` |
| `POST_PROCESS` | HTTP成功后，驱动处理前 | `{ file, fileItem, response }` |
| `UPLOAD_SUCCESS` | 上传成功 | `{ file, fileItem, response }` |
| `UPLOAD_ERROR` | 上传失败 | `{ file, fileItem, error }` |
| `UPLOAD_CANCELLED` | 上传取消 | `{ file, fileItem }` |
| `STATS_UPDATED` | 统计信息更新 | `{ stats, timestamp }` |
| `FILE_REMOVED` | 文件移除 | `{ file, fileItem }` |
| `ALL_PAUSED` | 所有上传暂停 | `{ pausedCount, totalFiles }` |
| `ALL_RESUMED` | 所有上传恢复 | `{ resumedCount, totalFiles }` |
| `DESTROY` | 实例销毁 | `{ timestamp }` |

### 事件监听方式

#### 1. 配置回调函数

```javascript
const uploader = new DolphinUploaderCore({
    // 事件回调函数命名规则：on + 驼峰式事件名
    onUploaderInit: (event, data) => {
        console.log('上传器初始化完成');
    },
    
    onUploaderFileAdded: (event, data) => {
        console.log('文件添加:', data.fileItem.name);
    },
    
    onUploaderUploadProgress: (event, data) => {
        console.log('上传进度:', data.progress + '%');
    },
    
    onUploaderUploadSuccess: (event, data) => {
        console.log('上传成功:', data.file.name);
    }
});
```

#### 2. 阻止事件（在 BEFORE 类事件中）

```javascript
const uploader = new DolphinUploaderCore({
    onUploaderBeforeUpload: (event, data) => {
        // 检查文件名是否包含敏感词
        if (data.file.name.includes('forbidden')) {
            event.preventDefault(); // 阻止此文件上传
            return false;           // 或者直接返回 false
        }
    },
    
    onUploaderBeforeBatchUpload: (event, data) => {
        // 检查是否达到每日上传限制
        if (getDailyUploadCount() >= 100) {
            event.preventDefault(); // 阻止批量上传
            alert('今日上传次数已达上限');
        }
    }
});
```

## 🔧 高级用法

### 自定义额外参数

```javascript
const uploader = new DolphinUploaderCore({
    // 静态额外参数
    extraData: {
        userId: 123,
        category: 'avatar'
    },
    
    // 动态额外参数
    extraData: async (fileItem) => {
        return {
            userId: getCurrentUserId(),
            timestamp: Date.now(),
            fileHash: fileItem.hash,
            category: detectFileCategory(fileItem.file)
        };
    }
});
```

### 自定义验证逻辑

```javascript
const uploader = new DolphinUploaderCore({
    onUploaderBeforeUpload: (event, data) => {
        const file = data.file;
        
        // 自定义文件名验证
        if (!/^[a-zA-Z0-9._-]+$/.test(file.name)) {
            event.preventDefault();
            showError('文件名包含非法字符');
            return;
        }
        
        // 自定义内容验证
        if (file.type.startsWith('image/')) {
            validateImageFile(file).then(isValid => {
                if (!isValid) {
                    uploader.cancelFiles([data.fileItem.id]);
                    showError('图片格式不正确');
                }
            });
        }
    }
});
```

### 进度管理和UI更新

```javascript
class UploadManager {
    constructor() {
        this.uploader = new DolphinUploaderCore({
            onUploaderFileAdded: (event, data) => {
                this.addFileToUI(data.fileItem);
            },
            
            onUploaderUploadProgress: (event, data) => {
                this.updateProgress(data.file.id, data.progress);
                this.updateSpeed(data.file.id, data.fileItem.uploadSpeed);
            },
            
            onUploaderUploadSuccess: (event, data) => {
                this.markFileSuccess(data.file.id, data.response);
            },
            
            onUploaderStatsUpdated: (event, data) => {
                this.updateOverallStats(data.stats);
            }
        });
    }
    
    addFileToUI(fileItem) {
        const fileElement = document.createElement('div');
        fileElement.id = `file-${fileItem.id}`;
        fileElement.innerHTML = `
            <div class="file-name">${fileItem.name}</div>
            <div class="file-size">${this.uploader.formatSize(fileItem.size)}</div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
            </div>
            <div class="file-status">等待上传...</div>
        `;
        document.getElementById('file-list').appendChild(fileElement);
    }
    
    updateProgress(fileId, progress) {
        const element = document.getElementById(`file-${fileId}`);
        if (element) {
            const progressFill = element.querySelector('.progress-fill');
            const status = element.querySelector('.file-status');
            
            progressFill.style.width = progress + '%';
            status.textContent = `上传中... ${Math.round(progress)}%`;
        }
    }
}
```

## 🌐 服务器端接口

### 文件上传接口 (`/admin/api/upload`)

**请求参数：**
```
POST /admin/api/upload
Content-Type: multipart/form-data

file: 文件对象
hash: 文件SHA1哈希值（可选）
sampleHash: 文件采样哈希值（可选）
...extraData: 自定义额外参数
```

**响应格式：**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "url": "https://example.com/files/abc.jpg",
        "name": "image.jpg",
        "size": 1024,
        "type": "image/jpeg",
        "hash": "da39a3ee5e6b4b0d3255bfef95601890afd80709"
    }
}
```

### 文件检查接口 (`/admin/api/checkFile`)

**请求参数：**
```json
{
    "hash": "da39a3ee5e6b4b0d3255bfef95601890afd80709",
    "size": 1024,
    "name": "image.jpg",
    "type": "image/jpeg"
}
```

**响应格式：**
```json
{
    "exists": true,
    "data": {
        "id": 123,
        "url": "https://example.com/files/abc.jpg"
    }
}
```

### 分片上传接口 (`/admin/api/uploadChunk`)

**请求参数：**
```
POST /admin/api/uploadChunk
Content-Type: multipart/form-data

chunk: 分片文件对象
chunkIndex: 分片索引（从0开始）
totalChunks: 总分片数
hash: 文件哈希值
filename: 原始文件名
```

**响应格式：**
```json
{
    "success": true,
    "chunkIndex": 0,
    "received": true
}
```

### 分片合并接口 (`/admin/api/mergeChunks`)

**请求参数：**
```json
{
    "hash": "da39a3ee5e6b4b0d3255bfef95601890afd80709",
    "totalChunks": 10,
    "filename": "video.mp4",
    "size": 1048576
}
```

**响应格式：**
```json
{
    "success": true,
    "data": {
        "id": 456,
        "url": "https://example.com/files/video.mp4",
        "name": "video.mp4",
        "size": 1048576
    }
}
```

## 🎨 与UI框架集成

### Vue.js 集成

```vue
<template>
    <div>
        <div @click="selectFiles" @drop="handleDrop" @dragover.prevent>
            点击或拖拽上传文件
        </div>
        <div v-for="file in files" :key="file.id">
            <div>{{ file.name }}</div>
            <div>{{ file.progress }}%</div>
        </div>
    </div>
</template>

<script>
import { DolphinUploaderCore } from './uploader-core.js';

export default {
    data() {
        return {
            uploader: null,
            files: []
        };
    },
    
    mounted() {
        this.uploader = new DolphinUploaderCore({
            onUploaderFileAdded: (event, data) => {
                this.files.push(data.file);
            },
            onUploaderUploadProgress: (event, data) => {
                const file = this.files.find(f => f.id === data.file.id);
                if (file) {
                    file.progress = data.progress;
                }
            }
        });
    },
    
    methods: {
        selectFiles() {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = true;
            input.onchange = (e) => {
                this.uploader.addFiles(e.target.files);
            };
            input.click();
        },
        
        handleDrop(e) {
            e.preventDefault();
            this.uploader.addFiles(e.dataTransfer.files);
        }
    }
};
</script>
```

### React 集成

```jsx
import React, { useEffect, useState, useRef } from 'react';
import { DolphinUploaderCore } from './uploader-core.js';

function FileUploader() {
    const [files, setFiles] = useState([]);
    const uploaderRef = useRef(null);
    
    useEffect(() => {
        uploaderRef.current = new DolphinUploaderCore({
            onUploaderFileAdded: (event, data) => {
                setFiles(prev => [...prev, data.file]);
            },
            onUploaderUploadProgress: (event, data) => {
                setFiles(prev => prev.map(file => 
                    file.id === data.file.id 
                        ? { ...file, progress: data.progress }
                        : file
                ));
            }
        });
        
        return () => {
            uploaderRef.current?.destroy();
        };
    }, []);
    
    const handleFileSelect = (e) => {
        if (e.target.files.length > 0) {
            uploaderRef.current.addFiles(e.target.files);
        }
    };
    
    return (
        <div>
            <input type="file" multiple onChange={handleFileSelect} />
            {files.map(file => (
                <div key={file.id}>
                    <div>{file.name}</div>
                    <div>{file.progress}%</div>
                </div>
            ))}
        </div>
    );
}
```

## 🐛 调试和故障排除

### 启用调试模式

```javascript
const uploader = new DolphinUploaderCore({
    debug: true, // 启用调试模式
    
    // 所有事件都会在控制台输出详细信息
});
```

### 常见问题

#### 1. 文件上传失败
```javascript
const uploader = new DolphinUploaderCore({
    onUploaderUploadError: (event, data) => {
        console.error('上传失败:', data.error);
        console.log('文件信息:', data.file);
        
        // 检查常见问题
        if (data.error.message.includes('413')) {
            alert('文件太大，请选择较小的文件');
        } else if (data.error.message.includes('415')) {
            alert('不支持的文件类型');
        }
    }
});
```

#### 2. 分片上传问题
```javascript
const uploader = new DolphinUploaderCore({
    chunked: true,
    debug: true,
    
    onUploaderUploadProgress: (event, data) => {
        if (data.chunkIndex !== undefined) {
            console.log(`分片 ${data.chunkIndex + 1}/${data.totalChunks} 上传完成`);
        }
    }
});
```

#### 3. 内存占用过高
```javascript
const uploader = new DolphinUploaderCore({
    // 启用内存优化
    memoryOptimized: true,
    
    // 减少并发数
    concurrent: 1,
    
    // 使用较小的分片
    chunkSize: '512KB',
    
    // 大文件使用快速哈希
    checkStrategy: 'hybrid'
});
```

## 📊 性能优化建议

### 1. 合理设置并发数
```javascript
// 根据用户网络环境调整
const uploader = new DolphinUploaderCore({
    concurrent: navigator.connection?.effectiveType === '4g' ? 3 : 1
});
```

### 2. 选择合适的哈希策略
```javascript
const uploader = new DolphinUploaderCore({
    checkStrategy: 'hybrid', // 大文件使用采样哈希，小文件使用完整哈希
});
```

### 3. 优化分片大小
```javascript
const uploader = new DolphinUploaderCore({
    chunkSize: '1MB', // 一般网络环境
    // chunkSize: '2MB', // 良好网络环境
    // chunkSize: '512KB', // 较差网络环境
});
```

## 📝 更新日志

### v1.0.0
- ✨ 首次发布
- 🚀 完整的分片上传和断点续传功能
- 🔐 安全的文件验证和哈希计算
- 🎛️ 丰富的配置选项和预设配置
- 📡 完整的事件系统
- 🎨 纯逻辑设计，无UI依赖

## 📄 许可证

MIT License

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 💬 支持

如有问题或建议，请通过以下方式联系：

- GitHub Issues
- 邮箱：support@example.com
- 文档：查看 `examples.html` 获取更多示例