import { getAuth } from "@/helpers/getAuth";
import Link from "next/link";

export default async function Home() {
  const { isAuthenticated, user, getAccessToken } = await getAuth();
  return (
    <div className="w-full space-y-5 min-h-screen flex flex-col items-center justify-center">
      <div className="w-[768px] bg-gray-200 flex flex-col justify-center items-center rounded-lg p-4 shadow-sm ">
        <h1 className="text-4xl font-bold">Ahmad Zidni Auth</h1>
        <pre className="overflow-x-auto max-w-full">
          {JSON.stringify(
            { isAuthenticated, accsessTtoken: await getAccessToken(), user },
            null,
            2
          )}
        </pre>
      </div>

      <div>
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
            href="/login"
          >
            Go to login
          </Link>
        )}
      </div>
    </div>
  );
}
