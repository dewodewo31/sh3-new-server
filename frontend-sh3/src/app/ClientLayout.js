'use client';

import { AuthProvider } from "@/src/contexts/AuthContext";

export default function ClientLayout({ children }) {
    return (
        <AuthProvider>
            {children}
        </AuthProvider>
    );
}