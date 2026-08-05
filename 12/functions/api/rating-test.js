export async function onRequest(context) {
    return Response.json({
        ok: true,
        message: "Ratings API route is working",
        pathname: new URL(context.request.url).pathname
    });
}