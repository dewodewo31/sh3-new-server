// components/SelectInput.jsx
export default function SelectInput({ id, name, label, placehold, required = false, options = [], ...props }) {
  return (
    <div className="flex flex-col gap-1.5">
        <label>
            <span className=
            {`font-medium text-xl ${required ? "after:ml-0.5 after:text-red-500 after:content-['*']" : ""}`}
            htmlFor={id}>{label}</span>
        </label>

      <div className="relative">
        <select
          id={id}
          name={name}
          className="w-full bg-white outline-2 rounded-md outline-tertiary-normal px-3 pr-9 h-9 text-lg cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 hover:border-gray-400 transition"
          {...props}
        >
          <option value="">{placehold}</option>
          {options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      </div>
    </div>
  )
}