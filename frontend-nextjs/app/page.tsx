import Link from "next/link";

import { getAuth } from "@/helpers/getAuth";
import { Metadata } from "next";
import { ClientSideAuth } from "./components/ClientSideAuth";

export const metadata: Metadata = {
  title: "Home",
};

export default async function Home() {
  const { isAuthenticated, user } = await getAuth();
  return (
    <div className="w-full space-y-5 min-h-screen flex-col items-center justify-center">
      <header className="relative overflow-hidden bg-gradient-to-r from-slate-100 to-slate-200">
        <div className="absolute inset-0 bg-grid-slate-100/[0.05] bg-[size:20px_20px]" />
        <div className="relative mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
          <div className="text-center">
            <div className="inline-flex items-center space-x-2">
              <div className="h-1.5 w-1.5 rounded-full bg-primary" />
              <h1 className="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                NextJs + Laravel OAuth Implementation
              </h1>
            </div>
            <p className="mt-3 text-sm font-medium text-muted-foreground">
              created by
              <Link
                href="https://github.com/ahmaddzidnii"
                target="_blank"
              >
                <span className="text-primary hover:text-primary/90 transition-colors font-bold">
                  &nbsp;ahmaddzidnii
                </span>
              </Link>
            </p>
          </div>
        </div>
      </header>
      <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div className="block  p-6  bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700">
          <h5 className="mb-2 text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Client Side Rendering
          </h5>

          <ClientSideAuth />
        </div>
        <div className="block  p-6  bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700">
          <h5 className="mb-2 text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Server Side Rendering
          </h5>

          <pre
            className="mt-5 overflow-x-auto max-w-full [&::-webkit-scrollbar]:w-2
  [&::-webkit-scrollbar-track]:bg-gray-100
  [&::-webkit-scrollbar-thumb]:bg-gray-300
  dark:[&::-webkit-scrollbar-track]:bg-neutral-700
  dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500 pb-5"
          >
            {JSON.stringify({ isAuthenticated, user }, null, 2)}
          </pre>
          <div className="mt-5">
            {isAuthenticated ? (
              <Link
                className="text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                href="/dashboard"
              >
                Go to dashboard
              </Link>
            ) : (
              <Link
                className="text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                href="/auth/login"
              >
                Go to login
              </Link>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
