// components/RevealSection.jsx
import { useScrollReveal } from '../hooks/useScrollReveal'

export function RevealSection({ children, direction = 'up', delay = 0, className = '' }) {
  const ref = useScrollReveal()

  const dirClass = {
    up: 'translate-y-10',
    left: '-translate-x-10',
    right: 'translate-x-10',
    scale: 'scale-90',
  }[direction]

  return (
    <div
      ref={ref}
      style={{ transitionDelay: `${delay}ms` }}
      className={`
        opacity-0 ${dirClass}
        transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]
        [&.visible]:opacity-100 [&.visible]:translate-y-0
        [&.visible]:translate-x-0 [&.visible]:scale-100
        ${className}
      `}
    >
      {children}
    </div>
  )
}