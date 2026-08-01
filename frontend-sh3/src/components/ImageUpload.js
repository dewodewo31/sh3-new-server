"use client"
import { useState, useRef } from "react"
import { PhotoIcon } from "@heroicons/react/24/outline"

export default function ImageUpload({ id = "cover_photo", label = "Cover photo", required = false, onChange }) {
  const [preview, setPreview] = useState(null)
  const [isDragging, setIsDragging] = useState(false)
  const inputRef = useRef(null)

  const handleFile = (file) => {
    if (!file || !file.type.startsWith("image/")) return
    if (file.size > 10 * 1024 * 1024) return alert("File maksimal 10MB")

    const reader = new FileReader()
    reader.onload = (e) => setPreview({ url: e.target.result, name: file.name, size: file.size })
    reader.readAsDataURL(file)

    if (onChange) onChange(file) // ← kirim file ke parent
  }

  const handleRemove = () => {
    setPreview(null)
    if (onChange) onChange(null) // ← kasih tau parent file dihapus
  }

  const formatSize = (bytes) => {
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  }

  return (
    <div className="flex flex-col gap-1.5">
      <label className="text-xl font-medium">
        {label}
        {required && <span className="text-red-500 ml-0.5">*</span>}
      </label>

      <input
        ref={inputRef}
        type="file"
        id={id}
        name={id}
        accept="image/png,image/jpeg,image/gif"
        className="hidden"
        onChange={(e) => handleFile(e.target.files[0])}
      />

      {!preview ? (
        <div
          onClick={() => inputRef.current.click()}
          onDragOver={(e) => { e.preventDefault(); setIsDragging(true) }}
          onDragLeave={() => setIsDragging(false)}
          onDrop={(e) => { e.preventDefault(); setIsDragging(false); handleFile(e.dataTransfer.files[0]) }}
          className={`border-2 border-dashed p-10 flex flex-col items-center gap-2 cursor-pointer transition rounded-md
            ${isDragging ? "border-tertiary-normal  bg-indigo-50" : "border-tertiary-normal hover:border-neutral-text hover:bg-neutral-light-hover"}`}
        >
          <PhotoIcon className="w-9 h-9"/>
          <p className="text-sm border-neutral-text">
            <span className="text-secondary-bg font-bold">Upload a file</span> or drag and drop
          </p>
          <p className="text-xs text-text-colors">PNG, JPG, GIF up to 10MB</p>
        </div>
      ) : (
        <div className="flex items-center gap-3 bg-neutral-bg  p-3 border border-neutral-bg">
          <img src={preview.url} alt="preview" className="w-11 h-11 object-cover border border-neutral-bg shrink-0" />
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-gray-800 truncate">{preview.name}</p>
            <p className="text-xs text-neutral-dark">{formatSize(preview.size)}</p>
          </div>
          <button
            type="button"
            onClick={handleRemove} // ← pakai handleRemove
            className="text-text-colors hover:text-red-500 p-1 transition"
          >
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
              <path d="M4 4l8 8M12 4l-8 8"/>
            </svg>
          </button>
        </div>
      )}
    </div>
  )
}