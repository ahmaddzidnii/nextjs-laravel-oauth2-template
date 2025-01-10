import { Logout } from "@/app/components/Logout";
import { getAuth } from "@/helpers/getAuth";

export default async function DashboardPage() {
  const { isAuthenticated, user, getAccessToken } = await getAuth();
  return (
    <div className="w-full min-h-screen flex flex-col items-center justify-center">
      <div className="w-[768px] bg-gray-200 flex flex-col justify-center items-center rounded-lg p-4 shadow-sm">
        <h1 className="text-4xl font-bold">Server Side Rendering</h1>
        <pre className="mt-5 overflow-x-auto max-w-full">
          {JSON.stringify({ isAuthenticated, accsessToken: await getAccessToken(), user }, null, 2)}
        </pre>

        <Logout />
      </div>
    </div>
  );
}