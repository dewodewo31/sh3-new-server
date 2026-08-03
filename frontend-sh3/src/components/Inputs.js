export default function InputType({
label,
id,
required = false,
className = "",
name,
...props

}) {
  return (
    <div className={className}>
        <label>
            <span className=
            {`font-medium text-xl ${required ? "after:ml-0.5 after:text-red-500 after:content-['*']" : ""}`}
            htmlFor={id}>{label}</span>
        </label>
        <input name={name} id={id} {...props} className="outline-2 p-2 bg-white outline-tertiary-normal rounded-md" />
    </div>
  )
}