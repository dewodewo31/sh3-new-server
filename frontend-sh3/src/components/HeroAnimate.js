// components/HeroAnimate.js
"use client"

import { useEffect, useState } from "react"

export function HeroAnimate({ children, animation = "fadeDown", delay = 0, className = "" }) {
    const [mounted, setMounted] = useState(false)

    useEffect(() => {
        const timer = setTimeout(() => setMounted(true), delay)
        return () => clearTimeout(timer)
    }, [delay])

    const animations = {
        fadeDown: {
            initial: "opacity-0 -translate-y-10",
            animate: "opacity-100 translate-y-0",
            duration: "duration-1000",
        },
        fadeUp: {
            initial: "opacity-0 translate-y-10",
            animate: "opacity-100 translate-y-0",
            duration: "duration-700",
        },
        scaleUp: {
            initial: "opacity-0 scale-75",
            animate: "opacity-100 scale-100",
            duration: "duration-700",
        },
    }

    const { initial, animate } = animations[animation]

    return (
        <div
            className={`
                transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]
                ${mounted ? animate : initial}
                ${className}
            `}
        >
            {children}
        </div>
    )
}