// components/Container.js
export default function Container({ children, className="" }) {
  return (
    <div className= {`text-gray-800 ${className}`}>
      {children}
    </div>
  )
}