"use client"
import Link from "next/link"
import { useState, useEffect, useRef } from "react"
import { usePathname, useRouter } from "next/navigation"
import { useAuth } from "@/src/contexts/AuthContext"

export default function Navbar() {
  const { user, isLoggedIn, logout } = useAuth();
  const [isMounted, setIsMounted] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const [isOpen, setIsOpen] = useState(false)
  const [isDropdownOpen, setIsDropdownOpen] = useState(false)
  const dropdownRef = useRef(null)
  const pathname = usePathname()
  const router = useRouter()

  // Set mounted setelah client render
  useEffect(() => {
    setIsMounted(true);
  }, []);

  const isActive = (href) => pathname === href
  const isHome = pathname === "/";

  // Close dropdown when click outside
  useEffect(() => {
    function handleClickOutside(event) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsDropdownOpen(false)
      }
    }
    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  // Scroll effect untuk home
  useEffect(() => {
    if (!isHome) {
      return;
    }

    const handleScroll = () => {
      setIsScrolled(window.scrollY > 100);
    }
    window.addEventListener("scroll", handleScroll);

    return () => {
      window.removeEventListener("scroll", handleScroll);
    }
  }, [isHome]);

  // Tutup mobile menu saat pindah halaman
  useEffect(() => {
    setIsOpen(false)
  }, [pathname])

  // Style functions
  const getNavBg = () => {
    if (isHome && !isScrolled && !isOpen) {
      return "bg-transparent";
    }
    return "bg-primary-light";
  }

  const getNavColor = () => {
    if (isHome && !isScrolled && !isOpen) {
      return "text-neutral-lighter";
    }
    return "text-neutral-dark";
  }

  const getBurgColor = () => {
    if (isHome && !isScrolled && !isOpen) {
      return "bg-neutral-lighter";
    }
    return "bg-neutral-dark";
  }

  const getNavActive = () => {
    if (isHome && !isScrolled && !isOpen) {
      return "border-primary-text text-primary-text";
    }
    return "border-secondary-text text-secondary-text";
  }

  const getNavHover = () => {
    if (isHome && !isScrolled && !isOpen) {
      return "hover:text-primary-text";
    }
    return "hover:text-secondary-text";
  }

  // Handlers
  const handleLogout = async () => {
    setIsDropdownOpen(false)
    if (confirm("Yakin mau logout?")) {
      logout()
      router.push("/")
    }
  }

  const goToProfile = () => {
    setIsDropdownOpen(false)
    router.push("/members/detail")
  }

  // Data user dari context (AMAN untuk SSR)
  const userData = user;
  const hashId = userData?.hash_id || "";
  const name = userData?.name || "";
  const photo = userData?.photo || "";
  const isUserLoggedIn = isLoggedIn; // ← HANYA dari context

  return (
    <nav className={` px-8 py-4 shadow-sm fixed top-0 left-0 w-full z-50 transition-all ${getNavBg()} `}>
      <div className="flex items-center justify-between">

        {/* Logo */}
        <a className="flex items-center gap-2" href="/">
          <span className="text-4xl font-bold text-primary-normal">#</span>
          <div>
            <p className={`font-bold leading-tight ${getNavColor()}`}>Samarinda Hash</p>
            <p className={`text-xs ${getNavColor()}`}>House Harriers</p>
          </div>
        </a>

        {/* Menu Desktop */}
        <ul className="hidden md:flex items-center gap-10">
          {[
            { href: "/", label: "Home" },
            { href: "/about", label: "About" },
            { href: "/events", label: "Events" },
            { href: "/merchandise", label: "Merchandise" },
            { href: "/gallery", label: "Gallery" },
            { href: "/sponsor", label: "Sponsorship" },
          ].map((item) => (
            <li key={item.href}>
              <Link
                href={item.href}
                className={`transition-colors ${getNavHover()} ${isActive(item.href)
                  ? `border-b-2 ${getNavActive()}  pb-0.5`
                  : getNavColor()
                  }`}
              >
                {item.label}
              </Link>
            </li>
          ))}
        </ul>

        {/* Tombol Auth - DESKTOP */}
        <div className="hidden md:flex gap-4">
          {isMounted && isUserLoggedIn ? (
            // ====== SUDAH LOGIN ======
            <div className="relative" ref={dropdownRef}>
              <button
                onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                className="flex items-center space-x-3 focus:outline-none hover:bg-white/50 rounded-full px-3 py-2 transition-all"
              >
                {/* Avatar */}
                <div className="w-10 h-10 rounded-full bg-secondary-bg flex items-center justify-center overflow-hidden border-2 border-secondary-bg">
                  {photo ? (
                    <img
                      src={photo}
                      alt={name}
                      className="object-cover w-full h-full"
                    />
                  ) : (
                    <span className="text-white font-bold text-lg">
                      {name ? name.charAt(0).toUpperCase() : "?"}
                    </span>
                  )}
                </div>

                {/* Nama dan Hash ID */}
                <div className="hidden lg:block text-left">
                  <p className={`text-sm font-semibold ${getNavColor()}`}>
                    {name || "User"}
                  </p>
                  <p className={`text-xs font-mono ${getNavColor()} opacity-70`}>
                    {hashId || "------"}
                  </p>
                </div>

                {/* Chevron */}
                <svg
                  className={`w-4 h-4 ${getNavColor()} transition-transform ${isDropdownOpen ? "rotate-180" : ""}`}
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              {/* Dropdown */}
              {isDropdownOpen && (
                <div className="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 border border-gray-100 z-50">
                  <div className="px-4 py-3 border-b border-gray-100">
                    <p className="text-sm font-semibold text-gray-700">
                      {name || "User"}
                    </p>
                    <p className="text-xs text-gray-400 font-mono">
                      {hashId || "------"}
                    </p>
                  </div>
                  <button
                    onClick={goToProfile}
                    className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                  >
                    <span className="flex items-center space-x-2">
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      <span>Profile Saya</span>
                    </span>
                  </button>
                  <button
                    onClick={handleLogout}
                    className="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                  >
                    <span className="flex items-center space-x-2">
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                      </svg>
                      <span>Logout</span>
                    </span>
                  </button>
                </div>
              )}
            </div>
          ) : (
            // ====== BELUM LOGIN ======
            <>
              <Link
                href="/members/register"
                className="bg-emerald-600 text-white rounded-sm px-6 py-2.5 font-medium hover:bg-emerald-500 active:bg-emerald-400"
              >
                Registrasi Member
              </Link>
              <Link
                href="/members/detail"
                className="bg-emerald-600 text-white rounded-sm px-6 py-2.5 font-medium hover:bg-emerald-500 active:bg-emerald-400"
              >
                Login Member
              </Link>
            </>
          )}
        </div>

        {/* Burger Button */}
        <button
          className="md:hidden flex flex-col gap-1.5 p-2"
          onClick={() => setIsOpen(!isOpen)}
        >
          <span className={`block w-6 h-0.5 ${getBurgColor()} transition-all duration-300 ${isOpen ? "rotate-45 translate-y-2" : ""}`} />
          <span className={`block w-6 h-0.5 ${getBurgColor()} transition-all duration-300 ${isOpen ? "opacity-0" : ""}`} />
          <span className={`block w-6 h-0.5 ${getBurgColor()} transition-all duration-300 ${isOpen ? "-rotate-45 -translate-y-2" : ""}`} />
        </button>

      </div>

      {/* Menu Mobile */}
      <div className={`md:hidden transition-all duration-300 overflow-hidden ${isOpen ? "max-h-96 mt-4" : "max-h-0"}`}>
        <ul className="flex flex-col gap-4 pb-4">
          {[
            { href: "/", label: "Home" },
            { href: "/about", label: "About" },
            { href: "/events", label: "Events" },
            { href: "/merchandise", label: "Merchandise" },
            { href: "/gallery", label: "Gallery" },
            { href: "/sponsor", label: "Sponsorship" },
          ].map((item) => (
            <li key={item.href}>
              <Link
                href={item.href}
                className={`block transition-colors hover:text-secondary-text ${isActive(item.href)
                  ? "text-secondary-text font-medium"
                  : getNavColor()
                  }`}
              >
                {item.label}
              </Link>
            </li>
          ))}

          {/* Tombol Auth di Mobile */}
          <li className="flex flex-col gap-3 pt-2 border-t border-gray-200">
            {isMounted && isUserLoggedIn ? (
              // ====== SUDAH LOGIN (Mobile) ======
              <>
                <Link
                  href="/members/detail"
                  className="bg-secondary-bg text-white px-6 py-2.5 font-medium rounded-sm hover:bg-secondary-bg-hover text-center"
                >
                  Profile Saya
                </Link>
                <button
                  onClick={handleLogout}
                  className="border-red-600 text-red-600  px-6 py-2.5 font-medium rounded-sm text-center border-2 hover:bg-red-600/10"
                >
                  Logout
                </button>
              </>
            ) : (
              // ====== BELUM LOGIN (Mobile) ======
              <>
                <Link
                  href="/members/register"
                  className="bg-emerald-600 text-white px-6 py-2.5 font-medium rounded-sm hover:bg-emerald-500 active:bg-emerald-400 text-center"
                >
                  Registrasi Member
                </Link>
                <Link
                  href="/members/detail"
                  className="bg-emerald-600 text-white px-6 py-2.5 font-medium rounded-sm hover:bg-emerald-500 active:bg-emerald-400 text-center"
                >
                  Login Member
                </Link>
              </>
            )}
          </li>
        </ul>
      </div>

    </nav>
  )
}