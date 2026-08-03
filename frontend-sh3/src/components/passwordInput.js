import { useState } from "react";
import { EyeIcon, EyeSlashIcon } from "@heroicons/react/24/outline";

export default function PasswordInput({
  label,
  id,
  required = false,
  className = "",
  name,
  value,
  onChange,
  placeholder = "",
  ...props
}) {
  const [showPassword, setShowPassword] = useState(false);


  const togglePassword = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setShowPassword(!showPassword);
  };

  return (
    <div className={className}>
      <label>
        <span
          className={`font-medium text-xl ${
            required ? "after:ml-0.5 after:text-red-500 after:content-['*']" : ""
          }`}
          htmlFor={id}
        >
          {label}
        </span>
      </label>
      <div className="relative w-full">
        <input
          id={id}
          name={name}
          type={showPassword ? "text" : "password"}
          value={value}
          onChange={onChange}
          placeholder={placeholder}
          {...props}
          className="outline-2 p-3 bg-white outline-tertiary-normal rounded-md w-full pr-12"
        />
        <button
          type="button"
          onClick={togglePassword}
          onMouseDown={(e) => e.preventDefault()}
          className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none z-10"
          style={{ 
            cursor: 'pointer',
            background: 'transparent',
            padding: '8px',
            borderRadius: '4px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            minWidth: '36px',
            minHeight: '36px',
          }}
        >
          {showPassword ? (
            <EyeSlashIcon className="w-5 h-5" />
          ) : (
            <EyeIcon className="w-5 h-5" />
          )}
        </button>
      </div>
    </div>
  );
}