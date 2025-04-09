import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect } from "react";
export default function Dashboard({ threads }) {
    useEffect(() => {
        import("../bot").then((module) => {
            console.log("Bot module loaded:", module);
        }).catch((error) => {
            console.error("Error loading bot module:", error);
        });
    }, []);
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />
            <div className="mt-6 max-w-[1200px] m-auto bg-white shadow-sm sm:rounded-lg">
                <div className="p-4">
                    <h3 className="text-lg font-semibold text-gray-800">
                        Latest Threads
                    </h3>

                    <div className="overflow-x-auto mt-4">
                        <table className="w-full border border-gray-300 rounded-lg">
                            <thead className="bg-gray-100 border-b border-gray-300">
                                <tr>
                                    <th className="p-3 text-left text-gray-700">Thread ID</th>
                                    <th className="p-3 text-left text-gray-700">Created At</th>
                                    <th className="p-3 text-left text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {threads.length > 0 ? (
                                    threads.map((thread) => (
                                        <tr key={thread.id} className="border-b hover:bg-gray-50">
                                            <td className="p-3">{thread.thread_id}</td>
                                            <td className="p-3">{new Date(thread.created_at).toLocaleString()}</td>
                                            <td className="p-3">
                                                <a
                                                    href={`https://platform.openai.com/playground/assistants?assistant=asst_leuwYDHmc7PnaWWUDMYkNNiU&mode=assistant&thread=${thread.thread_id}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600"
                                                >
                                                    View Thread
                                                </a>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="2" className="p-3 text-center text-gray-500">
                                            No threads found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="custom-bot">

            </div>
        </AuthenticatedLayout>
    );
}
